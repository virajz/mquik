<?php

namespace App\Modules\DistanceSlabMaster\Importers;

use App\Modules\DistanceSlabMaster\Models\DistanceSlabMaster;
use App\Modules\ImportExport\Contracts\Importable;
use Illuminate\Support\Facades\Validator;

class DistanceSlabImporter implements Importable
{
    public function label(): string
    {
        return 'Distance Slabs';
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
            'min_km' => [
                'label' => 'Min KM',
                'required' => true,
                'type' => 'integer',
                'help' => 'Start of the band, inclusive.',
            ],
            'max_km' => [
                'label' => 'Max KM',
                'required' => false,
                'type' => 'integer',
                'help' => 'End of the band; blank means "and above".',
            ],
            'charge_amount' => [
                'label' => 'Charge',
                'required' => true,
                'type' => 'decimal',
                'help' => 'Amount charged for this band.',
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
            'min_km' => ['required', 'integer', 'min:0'],
            'max_km' => ['nullable', 'integer', 'min:0', 'gte:min_km'],
            'charge_amount' => ['required', 'numeric', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        return $validator->errors()->all();
    }

    public function createRecord(array $data): void
    {
        DistanceSlabMaster::create($this->normalize($data));
    }

    public function updateRecord(object $existing, array $data): void
    {
        /** @var DistanceSlabMaster $existing */
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
