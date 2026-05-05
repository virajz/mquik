<?php

namespace App\Modules\VehicleBrandMaster\Exporters;

use App\Modules\ImportExport\Contracts\Exportable;
use App\Modules\VehicleBrandMaster\Models\VehicleBrandMaster;
use Illuminate\Database\Eloquent\Builder;

class VehicleBrandExporter implements Exportable
{
    public function label(): string
    {
        return 'Vehicle Brands';
    }

    public function headers(): array
    {
        return ['ID', 'Name', 'Code', 'Country', 'Active', 'Notes', 'Created At'];
    }

    public function query(): Builder
    {
        return VehicleBrandMaster::query()->orderBy('name');
    }

    public function row(object $model): array
    {
        return [$model->id, $model->name, $model->code, $model->country, $model->is_active ? 'YES' : 'NO', $model->notes, $model->created_at?->toIso8601String()];
    }

    public function fileName(): string
    {
        return 'vehicle-brands';
    }
}
