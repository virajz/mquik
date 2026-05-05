<?php

namespace App\Modules\VehicleColorMaster\Exporters;

use App\Modules\ImportExport\Contracts\Exportable;
use App\Modules\VehicleColorMaster\Models\VehicleColorMaster;
use Illuminate\Database\Eloquent\Builder;

class VehicleColorExporter implements Exportable
{
    public function label(): string
    {
        return 'Vehicle Colors';
    }

    public function headers(): array
    {
        return ['ID', 'Name', 'Hex', 'Active', 'Notes', 'Created At'];
    }

    public function query(): Builder
    {
        return VehicleColorMaster::query()->orderBy('name');
    }

    public function row(object $model): array
    {
        return [$model->id, $model->name, $model->hex_code, $model->is_active ? 'YES' : 'NO', $model->notes, $model->created_at?->toIso8601String()];
    }

    public function fileName(): string
    {
        return 'vehicle-colors';
    }
}
