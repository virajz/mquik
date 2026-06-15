<?php

namespace App\Modules\VehicleSegmentMaster\Exporters;

use App\Modules\ImportExport\Contracts\Exportable;
use App\Modules\VehicleSegmentMaster\Models\VehicleSegmentMaster;
use Illuminate\Database\Eloquent\Builder;

class VehicleSegmentExporter implements Exportable
{
    public function label(): string
    {
        return 'Vehicle Types';
    }

    public function headers(): array
    {
        return ['ID', 'Name', 'Code', 'Active', 'Notes', 'Created At'];
    }

    public function query(): Builder
    {
        return VehicleSegmentMaster::query()->orderBy('name');
    }

    /**
     * @param  VehicleSegmentMaster  $model
     */
    public function row(object $model): array
    {
        return [
            $model->id,
            $model->name,
            $model->code,
            $model->is_active ? 'YES' : 'NO',
            $model->notes,
            $model->created_at?->toIso8601String(),
        ];
    }

    public function fileName(): string
    {
        return 'vehicle-segments';
    }
}
