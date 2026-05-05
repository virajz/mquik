<?php

namespace App\Modules\CustomerVehicleMaster\Importers;

use App\Modules\CustomerMaster\Models\CustomerMaster;
use App\Modules\CustomerVehicleMaster\Models\CustomerVehicleMaster;
use App\Modules\ImportExport\Contracts\Importable;
use App\Modules\VehicleBrandMaster\Models\VehicleBrandMaster;
use App\Modules\VehicleColorMaster\Models\VehicleColorMaster;
use App\Modules\VehicleModelMaster\Models\VehicleModelMaster;
use App\Modules\VehicleVariantMaster\Models\VehicleVariantMaster;
use Illuminate\Support\Facades\Validator;

class CustomerVehicleImporter implements Importable
{
    public function label(): string
    {
        return 'Customer Vehicles';
    }

    public function columns(): array
    {
        return [
            'customer_phone' => ['label' => 'Customer Phone', 'required' => true, 'type' => 'string', 'help' => 'Must match an existing customer phone'],
            'brand_name' => ['label' => 'Brand', 'required' => true, 'type' => 'string'],
            'model_name' => ['label' => 'Model', 'required' => true, 'type' => 'string'],
            'variant_name' => ['label' => 'Variant', 'required' => false, 'type' => 'string'],
            'color_name' => ['label' => 'Color', 'required' => false, 'type' => 'string'],
            'registration_no' => ['label' => 'Registration No', 'required' => true, 'type' => 'string'],
            'year_of_manufacture' => ['label' => 'Year', 'required' => false, 'type' => 'integer'],
            'vin' => ['label' => 'VIN', 'required' => false, 'type' => 'string'],
            'engine_no' => ['label' => 'Engine No', 'required' => false, 'type' => 'string'],
            'odometer_km' => ['label' => 'Odometer KM', 'required' => false, 'type' => 'integer'],
            'insurance_expiry' => ['label' => 'Insurance Expiry', 'required' => false, 'type' => 'date'],
            'puc_expiry' => ['label' => 'PUC Expiry', 'required' => false, 'type' => 'date'],
            'is_active' => ['label' => 'Active', 'required' => false, 'type' => 'boolean', 'default' => true],
            'notes' => ['label' => 'Notes', 'required' => false, 'type' => 'string'],
        ];
    }

    public function uniqueBy(): array
    {
        return ['registration_no'];
    }

    public function validateRow(array $data): array
    {
        $errors = [];

        $customer = isset($data['customer_phone']) ? CustomerMaster::where('phone', $data['customer_phone'])->first() : null;
        if (! $customer) {
            $errors[] = "Customer with phone '{$data['customer_phone']}' not found";
        }

        $brandName = isset($data['brand_name']) ? strtoupper((string) $data['brand_name']) : null;
        $brand = $brandName ? VehicleBrandMaster::where('name', $brandName)->first() : null;
        if (! $brand) {
            $errors[] = "Brand '{$brandName}' not found";
        }

        $modelName = isset($data['model_name']) ? strtoupper((string) $data['model_name']) : null;
        $model = ($brand && $modelName) ? VehicleModelMaster::where('brand_id', $brand->id)->where('name', $modelName)->first() : null;
        if ($brand && ! $model) {
            $errors[] = "Model '{$modelName}' not found under brand '{$brandName}'";
        }

        $fieldErrors = Validator::make($data, [
            'registration_no' => ['required', 'string', 'max:20'],
            'year_of_manufacture' => ['nullable', 'integer', 'min:1980', 'max:'.((int) date('Y') + 1)],
            'vin' => ['nullable', 'string', 'size:17'],
            'engine_no' => ['nullable', 'string', 'max:30'],
            'odometer_km' => ['nullable', 'integer', 'min:0'],
            'insurance_expiry' => ['nullable', 'date'],
            'puc_expiry' => ['nullable', 'date'],
            'is_active' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ])->errors()->all();

        return array_merge($errors, $fieldErrors);
    }

    public function createRecord(array $data): void
    {
        CustomerVehicleMaster::create($this->normalize($data));
    }

    public function updateRecord(object $existing, array $data): void
    {
        $existing->update($this->normalize($data));
    }

    protected function normalize(array $data): array
    {
        // Resolve FKs
        if (isset($data['customer_phone'])) {
            $data['customer_id'] = CustomerMaster::where('phone', $data['customer_phone'])->firstOrFail()->id;
        }
        if (isset($data['brand_name']) && isset($data['model_name'])) {
            $brand = VehicleBrandMaster::where('name', strtoupper($data['brand_name']))->firstOrFail();
            $model = VehicleModelMaster::where('brand_id', $brand->id)
                ->where('name', strtoupper($data['model_name']))
                ->firstOrFail();
            $data['model_id'] = $model->id;

            if (! empty($data['variant_name'])) {
                $variant = VehicleVariantMaster::where('model_id', $model->id)
                    ->where('name', strtoupper($data['variant_name']))
                    ->first();
                $data['variant_id'] = $variant?->id;
            }
        }
        if (! empty($data['color_name'])) {
            $color = VehicleColorMaster::where('name', strtoupper($data['color_name']))->first();
            $data['color_id'] = $color?->id;
        }

        unset($data['customer_phone'], $data['brand_name'], $data['model_name'], $data['variant_name'], $data['color_name']);

        // Uppercase textual fields
        foreach (['registration_no', 'vin', 'engine_no', 'notes'] as $k) {
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
