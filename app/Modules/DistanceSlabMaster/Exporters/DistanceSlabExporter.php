<?php

namespace App\Modules\DistanceSlabMaster\Exporters;

use App\Modules\DistanceSlabMaster\Models\DistanceSlabMaster;
use App\Modules\ImportExport\Contracts\Exportable;
use Illuminate\Database\Eloquent\Builder;

class DistanceSlabExporter implements Exportable
{
    public function label(): string
    {
        return 'Distance Slabs';
    }

    public function headers(): array
    {
        return ['ID', 'Name', 'Code', 'Min KM', 'Max KM', 'Charge', 'Active', 'Notes', 'Created At'];
    }

    public function query(): Builder
    {
        return DistanceSlabMaster::query()->orderBy('min_km');
    }

    /**
     * @param  DistanceSlabMaster  $model
     */
    public function row(object $model): array
    {
        return [
            $model->id,
            $model->name,
            $model->code,
            $model->min_km,
            $model->max_km,
            $model->charge_amount,
            $model->is_active ? 'YES' : 'NO',
            $model->notes,
            $model->created_at?->toIso8601String(),
        ];
    }

    public function fileName(): string
    {
        return 'distance-slabs';
    }
}
