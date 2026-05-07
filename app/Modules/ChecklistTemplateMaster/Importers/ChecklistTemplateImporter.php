<?php

namespace App\Modules\ChecklistTemplateMaster\Importers;

use App\Modules\ChecklistGroupMaster\Models\ChecklistGroupMaster;
use App\Modules\ChecklistTemplateMaster\Models\ChecklistTemplateMaster;
use App\Modules\ImportExport\Contracts\Importable;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class ChecklistTemplateImporter implements Importable
{
    public function label(): string
    {
        return 'Checklist Templates';
    }

    public function columns(): array
    {
        return [
            'name' => ['label' => 'Template Name', 'required' => true, 'type' => 'string', 'help' => 'Will be uppercased.'],
            'code' => ['label' => 'Code', 'required' => false, 'type' => 'string', 'help' => 'Short code (max 30 chars).'],
            'group_name' => ['label' => 'Group Name', 'required' => true, 'type' => 'string', 'help' => 'Resolved by name (case-insensitive). Group must exist.'],
            'applies_to' => ['label' => 'Applies To', 'required' => true, 'type' => 'string', 'help' => 'job_card | pickup | delivery | claim | generic'],
            'items' => ['label' => 'Items', 'required' => true, 'type' => 'string', 'help' => 'Pipe-separated list of LABEL:REQ or LABEL:OPT (e.g. RC COPY:REQ|DL COPY:REQ).'],
            'is_active' => ['label' => 'Active', 'required' => false, 'type' => 'boolean', 'default' => true, 'help' => 'YES/NO, true/false, or 1/0.'],
            'notes' => ['label' => 'Notes', 'required' => false, 'type' => 'string'],
        ];
    }

    public function uniqueBy(): array
    {
        return ['name'];
    }

    public function validateRow(array $data): array
    {
        $errors = Validator::make($data, [
            'name' => ['required', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:30'],
            'group_name' => ['required', 'string', 'max:255'],
            'applies_to' => ['required', Rule::in(ChecklistTemplateMaster::appliesToOptions())],
            'items' => ['required', 'string'],
            'is_active' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ])->errors()->all();

        if (! empty($data['group_name']) && ! $this->resolveGroupId($data['group_name'])) {
            $errors[] = 'Group "'.$data['group_name'].'" does not exist or is inactive.';
        }

        if (! empty($data['items']) && empty($this->parseItems($data['items']))) {
            $errors[] = 'At least one item is required.';
        }

        return $errors;
    }

    public function createRecord(array $data): void
    {
        ChecklistTemplateMaster::create($this->normalize($data));
    }

    public function updateRecord(object $existing, array $data): void
    {
        /** @var ChecklistTemplateMaster $existing */
        $existing->update($this->normalize($data));
    }

    protected function normalize(array $data): array
    {
        $skip = ['group_name', 'checklist_group_id', 'applies_to', 'items', 'is_active'];
        foreach ($data as $k => $v) {
            if (is_string($v) && ! in_array($k, $skip, true)) {
                $data[$k] = strtoupper($v);
            }
        }

        if (isset($data['applies_to']) && is_string($data['applies_to'])) {
            $data['applies_to'] = strtolower($data['applies_to']);
        }

        if (! array_key_exists('is_active', $data) || $data['is_active'] === null) {
            $data['is_active'] = true;
        }

        $data['checklist_group_id'] = $this->resolveGroupId($data['group_name'] ?? null);
        unset($data['group_name']);

        $data['items'] = $this->parseItems($data['items'] ?? '');

        return $data;
    }

    protected function resolveGroupId(?string $name): ?int
    {
        if (! $name) {
            return null;
        }

        return ChecklistGroupMaster::query()
            ->whereLike('name', $name, caseSensitive: false)
            ->value('id');
    }

    /**
     * Parse "LABEL:REQ|LABEL:OPT" into an items array.
     *
     * @return array<int, array{label: string, is_required: bool}>
     */
    protected function parseItems(string $raw): array
    {
        if (trim($raw) === '') {
            return [];
        }

        return collect(explode('|', $raw))
            ->map(fn ($chunk) => trim($chunk))
            ->filter()
            ->map(function (string $chunk) {
                $parts = explode(':', $chunk, 2);
                $label = strtoupper(trim($parts[0]));
                $flag = strtoupper(trim($parts[1] ?? 'REQ'));

                return [
                    'label' => $label,
                    'is_required' => $flag !== 'OPT',
                ];
            })
            ->reject(fn ($row) => $row['label'] === '')
            ->values()
            ->all();
    }
}
