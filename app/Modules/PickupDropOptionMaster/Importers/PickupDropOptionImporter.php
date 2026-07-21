<?php

namespace App\Modules\PickupDropOptionMaster\Importers;

use App\Modules\ImportExport\Contracts\Importable;
use App\Modules\PickupDropOptionMaster\Models\PickupDropOptionMaster;
use Illuminate\Support\Facades\Validator;

class PickupDropOptionImporter implements Importable
{
    public function label(): string
    {
        return 'Pickup/Drop Options';
    }

    public function columns(): array
    {
        return [
            'name' => [
                'label' => 'Name',
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
            'involves_pickup' => [
                'label' => 'Involves Pickup',
                'required' => false,
                'type' => 'boolean',
                'default' => false,
                'help' => 'YES/NO — workshop collects the vehicle.',
            ],
            'involves_drop' => [
                'label' => 'Involves Drop',
                'required' => false,
                'type' => 'boolean',
                'default' => false,
                'help' => 'YES/NO — workshop returns the vehicle.',
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
            'involves_pickup' => ['nullable', 'boolean'],
            'involves_drop' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        return $validator->errors()->all();
    }

    public function createRecord(array $data): void
    {
        PickupDropOptionMaster::create($this->normalize($data));
    }

    public function updateRecord(object $existing, array $data): void
    {
        /** @var PickupDropOptionMaster $existing */
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
        if (! array_key_exists('is_active', $data) || $data['is_active'] === null) {
            $data['is_active'] = true;
        }

        return $data;
    }
}
