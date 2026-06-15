<?php

namespace App\Modules\VehicleVariantMaster\Importers;

use App\Modules\FuelTypeMaster\Models\FuelTypeMaster;
use App\Modules\ImportExport\Contracts\Importable;
use App\Modules\TransmissionTypeMaster\Models\TransmissionTypeMaster;
use App\Modules\VehicleBrandMaster\Models\VehicleBrandMaster;
use App\Modules\VehicleModelMaster\Models\VehicleModelMaster;
use App\Modules\VehicleVariantMaster\Models\VehicleVariantMaster;
use Illuminate\Support\Facades\Validator;

class VehicleVariantImporter implements Importable
{
    public function label(): string
    {
        return 'Vehicle Variants';
    }

    public function columns(): array
    {
        return [
            'brand_name' => ['label' => 'Brand', 'required' => true, 'type' => 'string'],
            'model_name' => ['label' => 'Model', 'required' => true, 'type' => 'string'],
            'name' => ['label' => 'Variant', 'required' => true, 'type' => 'string'],
            'transmission' => ['label' => 'Transmission', 'required' => false, 'type' => 'string', 'help' => 'Match by name, e.g. MANUAL / AUTOMATIC / AMT'],
            'engine_cc' => ['label' => 'Engine', 'required' => false, 'type' => 'string'],
            'fuel_type' => ['label' => 'Fuel Type', 'required' => false, 'type' => 'string', 'help' => 'Match by name, e.g. PETROL / DIESEL / CNG'],
            'year' => ['label' => 'Year', 'required' => false, 'type' => 'integer'],
            'is_active' => ['label' => 'Active', 'required' => false, 'type' => 'boolean', 'default' => true],
            'notes' => ['label' => 'Notes', 'required' => false, 'type' => 'string'],
        ];
    }

    public function uniqueBy(): array
    {
        return ['model_id', 'name'];
    }

    public function validateRow(array $data): array
    {
        $brandName = isset($data['brand_name']) ? strtoupper((string) $data['brand_name']) : null;
        $brand = $brandName ? VehicleBrandMaster::where('name', $brandName)->first() : null;
        $modelName = isset($data['model_name']) ? strtoupper((string) $data['model_name']) : null;
        $model = ($brand && $modelName) ? VehicleModelMaster::where('brand_id', $brand->id)->where('name', $modelName)->first() : null;

        $errors = [];
        if (! $brand) {
            $errors[] = "Brand '{$brandName}' not found";
        }
        if ($brand && ! $model) {
            $errors[] = "Model '{$modelName}' not found under brand '{$brandName}'";
        }

        $fieldErrors = Validator::make($data, [
            'name' => ['required', 'string', 'max:255'],
            'transmission' => ['nullable', 'string', 'max:60'],
            'engine_cc' => ['nullable', 'string', 'max:20'],
            'fuel_type' => ['nullable', 'string', 'max:60'],
            'year' => ['nullable', 'integer', 'min:1980', 'max:'.(date('Y') + 1)],
            'is_active' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ])->errors()->all();

        if (filled($data['transmission'] ?? null) && ! TransmissionTypeMaster::where('name', strtoupper((string) $data['transmission']))->exists()) {
            $errors[] = "Transmission '{$data['transmission']}' not found";
        }
        if (filled($data['fuel_type'] ?? null) && ! FuelTypeMaster::where('name', strtoupper((string) $data['fuel_type']))->exists()) {
            $errors[] = "Fuel type '{$data['fuel_type']}' not found";
        }

        return array_merge($errors, $fieldErrors);
    }

    public function createRecord(array $data): void
    {
        VehicleVariantMaster::create($this->normalize($data));
    }

    public function updateRecord(object $existing, array $data): void
    {
        $existing->update($this->normalize($data));
    }

    protected function normalize(array $data): array
    {
        $brand = isset($data['brand_name']) ? VehicleBrandMaster::where('name', strtoupper($data['brand_name']))->firstOrFail() : null;
        if ($brand && isset($data['model_name'])) {
            $model = VehicleModelMaster::where('brand_id', $brand->id)
                ->where('name', strtoupper($data['model_name']))
                ->firstOrFail();
            $data['model_id'] = $model->id;
        }
        unset($data['brand_name'], $data['model_name']);

        if (isset($data['name'])) {
            $data['name'] = strtoupper((string) $data['name']);
        }
        if (isset($data['notes']) && is_string($data['notes'])) {
            $data['notes'] = strtoupper($data['notes']);
        }
        $data['transmission_type_id'] = filled($data['transmission'] ?? null)
            ? TransmissionTypeMaster::where('name', strtoupper((string) $data['transmission']))->value('id')
            : null;
        $data['fuel_type_id'] = filled($data['fuel_type'] ?? null)
            ? FuelTypeMaster::where('name', strtoupper((string) $data['fuel_type']))->value('id')
            : null;
        unset($data['transmission'], $data['fuel_type']);

        if (! array_key_exists('is_active', $data) || $data['is_active'] === null) {
            $data['is_active'] = true;
        }

        return $data;
    }
}
