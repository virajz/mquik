<?php

namespace App\Modules\VehicleColorMaster\Importers;

use App\Modules\ImportExport\Contracts\Importable;
use App\Modules\VehicleColorMaster\Models\VehicleColorMaster;
use Illuminate\Support\Facades\Validator;

class VehicleColorImporter implements Importable
{
    public function label(): string
    {
        return 'Vehicle Colors';
    }

    public function columns(): array
    {
        return [
            'name' => ['label' => 'Name', 'required' => true, 'type' => 'string'],
            'hex_code' => ['label' => 'Hex', 'required' => false, 'type' => 'string', 'help' => '#RRGGBB'],
            'is_active' => ['label' => 'Active', 'required' => false, 'type' => 'boolean', 'default' => true],
            'notes' => ['label' => 'Notes', 'required' => false, 'type' => 'string'],
        ];
    }

    public function uniqueBy(): array
    {
        return ['name'];
    }

    public function validateRow(array $data): array
    {
        return Validator::make($data, [
            'name' => ['required', 'string', 'max:255'],
            'hex_code' => ['nullable', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'is_active' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ])->errors()->all();
    }

    public function createRecord(array $data): void
    {
        VehicleColorMaster::create($this->normalize($data));
    }

    public function updateRecord(object $existing, array $data): void
    {
        $existing->update($this->normalize($data));
    }

    protected function normalize(array $data): array
    {
        if (isset($data['name']) && is_string($data['name'])) {
            $data['name'] = strtoupper($data['name']);
        }
        if (isset($data['notes']) && is_string($data['notes'])) {
            $data['notes'] = strtoupper($data['notes']);
        }
        if (isset($data['hex_code']) && is_string($data['hex_code'])) {
            $data['hex_code'] = strtoupper($data['hex_code']);
        }
        if (! array_key_exists('is_active', $data) || $data['is_active'] === null) {
            $data['is_active'] = true;
        }

        return $data;
    }
}
