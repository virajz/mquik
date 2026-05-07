<?php

namespace App\Modules\CustomerMaster\Exporters;

use App\Modules\CustomerMaster\Models\CustomerMaster;
use App\Modules\ImportExport\Contracts\Exportable;
use Illuminate\Database\Eloquent\Builder;

class CustomerExporter implements Exportable
{
    public function label(): string
    {
        return 'Customers';
    }

    public function headers(): array
    {
        return [
            'ID', 'Name', 'Type', 'Phone', 'Alternate Phone', 'Email',
            'Address', 'City', 'Pincode', 'Aadhar', 'PAN', 'Date of Birth',
            'Active', 'Created At',
        ];
    }

    public function query(): Builder
    {
        return CustomerMaster::query()->with('businessType:id,name')->latest('id');
    }

    /**
     * @param  CustomerMaster  $model
     */
    public function row(object $model): array
    {
        return [
            $model->id,
            $model->name,
            $model->businessType?->name,
            $model->phone,
            $model->alternate_phone,
            $model->email,
            $model->address,
            $model->city,
            $model->pincode,
            $model->aadhar,
            $model->pan,
            $model->date_of_birth?->format('Y-m-d'),
            $model->is_active ? 'YES' : 'NO',
            $model->created_at?->toIso8601String(),
        ];
    }

    public function fileName(): string
    {
        return 'customers';
    }
}
