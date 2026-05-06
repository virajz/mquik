<?php

namespace App\Modules\InventoryGroupMaster\Importers;

use App\Modules\ImportExport\Contracts\Importable;
use App\Modules\InventoryGroupMaster\Models\InventoryGroupMaster;
use Illuminate\Support\Facades\Validator;

class InventoryGroupImporter implements Importable
{
    public function label(): string
    {
        return 'Inventory Groups';
    }

    public function columns(): array
    {
        return [
            'name' => [
                'label' => 'Group Name',
                'required' => true,
                'type' => 'string',
                'help' => 'Will be uppercased.',
            ],
            'code' => [
                'label' => 'Code',
                'required' => false,
                'type' => 'string',
                'help' => 'Short code (max 20 chars).',
            ],
            'parent_name' => [
                'label' => 'Parent Group',
                'required' => false,
                'type' => 'string',
                'help' => 'Must match an existing group name (case-insensitive). Leave blank for top-level.',
            ],
            'is_active' => [
                'label' => 'Active',
                'required' => false,
                'type' => 'boolean',
                'default' => true,
                'help' => 'YES/NO, true/false, or 1/0.',
            ],
            'notes' => [
                'label' => 'Notes',
                'required' => false,
                'type' => 'string',
            ],
        ];
    }

    public function uniqueBy(): array
    {
        return ['name'];
    }

    public function validateRow(array $data): array
    {
        $errors = [];

        // Resolve parent_name if provided.
        $parentName = isset($data['parent_name']) ? trim((string) $data['parent_name']) : '';
        if ($parentName !== '') {
            $upper = strtoupper($parentName);
            $parent = InventoryGroupMaster::whereLike('name', $upper, caseSensitive: false)->first();
            if (! $parent) {
                $errors[] = "Parent group '{$parentName}' not found";
            }
        }

        $fieldErrors = Validator::make($data, [
            'name' => ['required', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:20'],
            'is_active' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ])->errors()->all();

        return array_merge($errors, $fieldErrors);
    }

    public function createRecord(array $data): void
    {
        InventoryGroupMaster::create($this->normalize($data));
    }

    public function updateRecord(object $existing, array $data): void
    {
        /** @var InventoryGroupMaster $existing */
        $existing->update($this->normalize($data));
    }

    /** Apply workshop conventions: uppercase strings, default booleans, resolve parent_name. */
    protected function normalize(array $data): array
    {
        if (isset($data['name']) && is_string($data['name'])) {
            $data['name'] = strtoupper($data['name']);
        }
        if (isset($data['code']) && is_string($data['code'])) {
            $data['code'] = strtoupper($data['code']);
        }

        // Resolve parent_name -> parent_id
        $parentName = isset($data['parent_name']) ? trim((string) $data['parent_name']) : '';
        if ($parentName !== '') {
            $parent = InventoryGroupMaster::whereLike('name', strtoupper($parentName), caseSensitive: false)->first();
            $data['parent_id'] = $parent?->id;
        } else {
            $data['parent_id'] = null;
        }
        unset($data['parent_name']);

        if (! array_key_exists('is_active', $data) || $data['is_active'] === null) {
            $data['is_active'] = true;
        }

        return $data;
    }
}
