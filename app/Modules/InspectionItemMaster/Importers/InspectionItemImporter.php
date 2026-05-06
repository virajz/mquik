<?php

namespace App\Modules\InspectionItemMaster\Importers;

use App\Modules\ImportExport\Contracts\Importable;
use App\Modules\InspectionItemGroupMaster\Models\InspectionItemGroupMaster;
use App\Modules\InspectionItemMaster\Models\InspectionItemMaster;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class InspectionItemImporter implements Importable
{
    public function label(): string
    {
        return 'Inspection Items';
    }

    public function columns(): array
    {
        return [
            'name' => ['label' => 'Item Name', 'required' => true, 'type' => 'string', 'help' => 'Will be uppercased.'],
            'code' => ['label' => 'Code', 'required' => false, 'type' => 'string', 'help' => 'Short code (max 30 chars).'],
            'group_name' => ['label' => 'Group', 'required' => false, 'type' => 'string', 'help' => 'Match by group name (case-insensitive). Optional.'],
            'check_type' => ['label' => 'Check Type', 'required' => true, 'type' => 'string', 'help' => 'visual | measurement | yes_no | rating'],
            'measurement_unit' => ['label' => 'Measurement Unit', 'required' => false, 'type' => 'string', 'help' => 'e.g. mm, %, bar, V — only for measurement check-type.'],
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
            'group_name' => ['nullable', 'string'],
            'check_type' => ['required', Rule::in(InspectionItemMaster::checkTypes())],
            'measurement_unit' => ['nullable', 'string', 'max:20'],
            'is_active' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ])->errors()->all();

        if (! empty($data['group_name']) && ! $this->resolveGroupId($data['group_name'])) {
            $errors[] = 'Group "'.$data['group_name'].'" does not exist or is inactive.';
        }

        return $errors;
    }

    public function createRecord(array $data): void
    {
        InspectionItemMaster::create($this->normalize($data));
    }

    public function updateRecord(object $existing, array $data): void
    {
        /** @var InspectionItemMaster $existing */
        $existing->update($this->normalize($data));
    }

    protected function normalize(array $data): array
    {
        $skip = ['group_name', 'check_type', 'is_active'];
        foreach ($data as $k => $v) {
            if (is_string($v) && ! in_array($k, $skip, true)) {
                $data[$k] = strtoupper($v);
            }
        }

        if (isset($data['check_type']) && is_string($data['check_type'])) {
            $data['check_type'] = strtolower($data['check_type']);
        }

        if (! array_key_exists('is_active', $data) || $data['is_active'] === null) {
            $data['is_active'] = true;
        }

        $data['inspection_item_group_id'] = $this->resolveGroupId($data['group_name'] ?? null);
        unset($data['group_name']);

        return $data;
    }

    protected function resolveGroupId(?string $name): ?int
    {
        if (! $name) {
            return null;
        }

        return InspectionItemGroupMaster::query()
            ->whereLike('name', $name, caseSensitive: false)
            ->where('is_active', true)
            ->value('id');
    }
}
