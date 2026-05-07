<?php

namespace App\Modules\VehicleModelMaster\Exporters;

use App\Modules\ImportExport\Contracts\Exportable;
use App\Modules\VehicleModelMaster\Models\VehicleModelMaster;
use Illuminate\Database\Eloquent\Builder;

class VehicleModelExporter implements Exportable
{
    public function label(): string
    {
        return 'Vehicle Models';
    }

    public function headers(): array
    {
        return ['ID', 'Brand', 'Model', 'Segment', 'Fuel', 'Active', 'Notes', 'Created At'];
    }

    public function query(): Builder
    {
        return VehicleModelMaster::query()->with(['brand:id,name', 'vehicleSegment:id,name'])->orderBy('name');
    }

    public function row(object $model): array
    {
        return [
            $model->id,
            $model->brand?->name,
            $model->name,
            $model->vehicleSegment?->name,
            $model->fuel_type,
            $model->is_active ? 'YES' : 'NO',
            $model->notes,
            $model->created_at?->toIso8601String(),
        ];
    }

    public function fileName(): string
    {
        return 'vehicle-models';
    }
}
