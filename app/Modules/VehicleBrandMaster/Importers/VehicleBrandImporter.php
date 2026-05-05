<?php

namespace App\Modules\VehicleBrandMaster\Importers;

use App\Modules\ImportExport\Contracts\Importable;
use App\Modules\VehicleBrandMaster\Models\VehicleBrandMaster;
use Illuminate\Support\Facades\Validator;

class VehicleBrandImporter implements Importable
{
    public function label(): string
    {
        return 'Vehicle Brands';
    }

    public function columns(): array
    {
        return [
            'name' => ['label' => 'Name', 'required' => true, 'type' => 'string'],
            'code' => ['label' => 'Code', 'required' => false, 'type' => 'string'],
            'country' => ['label' => 'Country', 'required' => false, 'type' => 'string'],
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
            'code' => ['nullable', 'string', 'max:20'],
            'country' => ['nullable', 'string', 'max:255'],
            'is_active' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ])->errors()->all();
    }

    public function createRecord(array $data): void
    {
        VehicleBrandMaster::create($this->normalize($data));
    }

    public function updateRecord(object $existing, array $data): void
    {
        $existing->update($this->normalize($data));
    }

    protected function normalize(array $data): array
    {
        foreach (['name', 'code', 'country', 'notes'] as $k) {
            if (isset($data[$k]) && is_string($data[$k])) {
                $data[$k] = strtoupper($data[$k]);
            }
        }
        if (! array_key_exists('is_active', $data) || $data['is_active'] === null) {
            $data['is_active'] = true;
        }

        return $data;
    }
}
