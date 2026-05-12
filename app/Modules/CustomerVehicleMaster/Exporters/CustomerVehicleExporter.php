<?php

namespace App\Modules\CustomerVehicleMaster\Exporters;

use App\Modules\CustomerVehicleMaster\Models\CustomerVehicleMaster;
use App\Modules\ImportExport\Contracts\Exportable;
use Illuminate\Database\Eloquent\Builder;

class CustomerVehicleExporter implements Exportable
{
    public function label(): string
    {
        return 'Customer Vehicles';
    }

    public function headers(): array
    {
        return [
            'ID', 'Registration No', 'Customer', 'Customer Phone',
            'Brand', 'Model', 'Variant', 'Color',
            'Year', 'VIN', 'Engine No', 'Odometer KM',
            'Active', 'Notes', 'Created At',
        ];
    }

    public function query(): Builder
    {
        return CustomerVehicleMaster::query()
            ->with(['customer:id,first_name,middle_name,last_name,phone', 'model.brand', 'variant:id,name', 'color:id,name'])
            ->orderBy('id', 'desc');
    }

    public function row(object $model): array
    {
        return [
            $model->id,
            $model->registration_no,
            $model->customer?->name,
            $model->customer?->phone,
            $model->model?->brand?->name,
            $model->model?->name,
            $model->variant?->name,
            $model->color?->name,
            $model->year_of_manufacture,
            $model->vin,
            $model->engine_no,
            $model->odometer_km,
            $model->is_active ? 'YES' : 'NO',
            $model->notes,
            $model->created_at?->toIso8601String(),
        ];
    }

    public function fileName(): string
    {
        return 'customer-vehicles';
    }
}
