<?php

namespace App\Modules\InspectionTemplateMaster\Importers;

use App\Modules\ImportExport\Contracts\Importable;
use App\Modules\InspectionItemMaster\Models\InspectionItemMaster;
use App\Modules\InspectionTemplateMaster\Models\InspectionTemplateMaster;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class InspectionTemplateImporter implements Importable
{
    public function label(): string
    {
        return 'Inspection Templates';
    }

    public function columns(): array
    {
        return [
            'name' => ['label' => 'Template Name', 'required' => true, 'type' => 'string', 'help' => 'Will be uppercased.'],
            'code' => ['label' => 'Code', 'required' => false, 'type' => 'string', 'help' => 'Short code (max 30 chars).'],
            'applies_to' => ['label' => 'Applies To', 'required' => true, 'type' => 'string', 'help' => 'pms | tyre | bodyshop | basic | custom'],
            'items' => ['label' => 'Items', 'required' => false, 'type' => 'string', 'help' => 'Comma-separated list of inspection item names. Each name resolved case-insensitively.'],
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
            'applies_to' => ['required', Rule::in(InspectionTemplateMaster::appliesToOptions())],
            'items' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ])->errors()->all();

        if (! empty($data['items'])) {
            $missing = $this->missingItemNames($data['items']);
            foreach ($missing as $name) {
                $errors[] = 'Item "'.$name.'" does not exist or is inactive.';
            }
        }

        return $errors;
    }

    public function createRecord(array $data): void
    {
        $itemIds = $this->resolveItemIds($data['items'] ?? null);
        $template = InspectionTemplateMaster::create($this->normalize($data));
        $this->syncItems($template, $itemIds);
    }

    public function updateRecord(object $existing, array $data): void
    {
        /** @var InspectionTemplateMaster $existing */
        $itemIds = $this->resolveItemIds($data['items'] ?? null);
        $existing->update($this->normalize($data));
        $this->syncItems($existing, $itemIds);
    }

    protected function normalize(array $data): array
    {
        $skip = ['applies_to', 'items', 'is_active'];
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

        unset($data['items']);

        return $data;
    }

    /** @return array<int, int> */
    protected function resolveItemIds(?string $csv): array
    {
        if (! $csv) {
            return [];
        }

        $names = collect(explode(',', $csv))
            ->map(fn ($n) => trim($n))
            ->filter()
            ->all();

        if (empty($names)) {
            return [];
        }

        return InspectionItemMaster::query()
            ->where('is_active', true)
            ->where(function ($q) use ($names) {
                foreach ($names as $name) {
                    $q->orWhereLike('name', $name, caseSensitive: false);
                }
            })
            ->pluck('id')
            ->all();
    }

    /** @return array<int, string> Names that could not be resolved. */
    protected function missingItemNames(string $csv): array
    {
        $names = collect(explode(',', $csv))
            ->map(fn ($n) => trim($n))
            ->filter()
            ->unique();

        if ($names->isEmpty()) {
            return [];
        }

        $found = InspectionItemMaster::query()
            ->where('is_active', true)
            ->where(function ($q) use ($names) {
                foreach ($names as $name) {
                    $q->orWhereLike('name', $name, caseSensitive: false);
                }
            })
            ->pluck('name')
            ->map(fn ($n) => mb_strtolower($n))
            ->all();

        return $names
            ->reject(fn ($name) => in_array(mb_strtolower($name), $found, true))
            ->values()
            ->all();
    }

    /** @param  array<int, int>  $itemIds */
    protected function syncItems(InspectionTemplateMaster $template, array $itemIds): void
    {
        $sync = collect($itemIds)
            ->values()
            ->mapWithKeys(fn ($id, $i) => [$id => ['position' => $i + 1, 'is_required' => true]])
            ->all();

        $template->items()->sync($sync);
    }
}
