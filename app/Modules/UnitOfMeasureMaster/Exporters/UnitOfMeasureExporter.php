<?php

namespace App\Modules\UnitOfMeasureMaster\Exporters;

use App\Modules\ImportExport\Contracts\Exportable;
use App\Modules\UnitOfMeasureMaster\Models\UnitOfMeasureMaster;
use Illuminate\Database\Eloquent\Builder;

class UnitOfMeasureExporter implements Exportable
{
    public function label(): string
    {
        return 'Units of Measure';
    }

    public function headers(): array
    {
        return ['ID', 'Name', 'Code', 'Active', 'Notes', 'Created At'];
    }

    public function query(): Builder
    {
        return UnitOfMeasureMaster::query()->orderBy('name');
    }

    /**
     * @param  UnitOfMeasureMaster  $model
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
        return 'units-of-measure';
    }
}
