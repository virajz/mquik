<?php

namespace App\Modules\VehicleVariantMaster\Importers;

use App\Modules\ImportExport\Contracts\Importable;
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
            'transmission' => ['label' => 'Transmission', 'required' => false, 'type' => 'string'],
            'engine_cc' => ['label' => 'Engine', 'required' => false, 'type' => 'string'],
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
            'transmission' => ['nullable', 'in:manual,automatic,amt,cvt,dct'],
            'engine_cc' => ['nullable', 'string', 'max:20'],
            'is_active' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ])->errors()->all();

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
        if (isset($data['transmission']) && is_string($data['transmission'])) {
            $data['transmission'] = strtolower($data['transmission']);
        }

        if (! array_key_exists('is_active', $data) || $data['is_active'] === null) {
            $data['is_active'] = true;
        }

        return $data;
    }
}
