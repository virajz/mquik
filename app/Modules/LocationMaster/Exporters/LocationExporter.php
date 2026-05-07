<?php

namespace App\Modules\LocationMaster\Exporters;

use App\Modules\ImportExport\Contracts\Exportable;
use App\Modules\LocationMaster\Models\LocationMaster;
use Illuminate\Database\Eloquent\Builder;

class LocationExporter implements Exportable
{
    public function label(): string
    {
        return 'Locations';
    }

    public function headers(): array
    {
        return ['ID', 'Code', 'Name', 'Head Office', 'Address', 'City', 'State', 'Pincode', 'Phone', 'Email', 'GSTIN', 'Active', 'Notes', 'Created At'];
    }

    public function query(): Builder
    {
        return LocationMaster::query()->with(['city:id,name', 'state:id,name'])->orderBy('name');
    }

    /**
     * @param  LocationMaster  $model
     */
    public function row(object $model): array
    {
        return [
            $model->id,
            $model->code,
            $model->name,
            $model->is_head_office ? 'YES' : 'NO',
            $model->address,
            $model->city?->name,
            $model->state?->name,
            $model->pincode,
            $model->phone,
            $model->email,
            $model->gstin,
            $model->is_active ? 'YES' : 'NO',
            $model->notes,
            $model->created_at?->toIso8601String(),
        ];
    }

    public function fileName(): string
    {
        return 'locations';
    }
}
