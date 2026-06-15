<?php

namespace App\Modules\PhotoTypeMaster\Importers;

use App\Modules\ImportExport\Contracts\Importable;
use App\Modules\PhotoTypeMaster\Models\PhotoTypeMaster;
use Illuminate\Support\Facades\Validator;

class PhotoTypeImporter implements Importable
{
    public function label(): string
    {
        return 'Photo Types';
    }

    public function columns(): array
    {
        return [
            'name' => [
                'label' => 'Type Name',
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
            'group' => [
                'label' => 'Tab / Group',
                'required' => false,
                'type' => 'string',
                'help' => 'Capture tab, e.g. EXTERIOR. Defaults to GENERAL.',
            ],
            'sort_order' => [
                'label' => 'Order',
                'required' => false,
                'type' => 'integer',
                'help' => 'Slot order within the tab.',
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
        $validator = Validator::make($data, [
            'name' => ['required', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:20'],
            'group' => ['nullable', 'string', 'max:60'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        return $validator->errors()->all();
    }

    public function createRecord(array $data): void
    {
        PhotoTypeMaster::create($this->normalize($data));
    }

    public function updateRecord(object $existing, array $data): void
    {
        /** @var PhotoTypeMaster $existing */
        $existing->update($this->normalize($data));
    }

    /** Apply workshop conventions: uppercase strings, default booleans. */
    protected function normalize(array $data): array
    {
        if (isset($data['name']) && is_string($data['name'])) {
            $data['name'] = strtoupper($data['name']);
        }
        if (isset($data['code']) && is_string($data['code'])) {
            $data['code'] = strtoupper($data['code']);
        }
        if (isset($data['group']) && is_string($data['group']) && $data['group'] !== '') {
            $data['group'] = strtoupper($data['group']);
        } else {
            $data['group'] = 'GENERAL';
        }
        if (! array_key_exists('is_active', $data) || $data['is_active'] === null) {
            $data['is_active'] = true;
        }

        return $data;
    }
}
