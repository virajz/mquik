<?php

namespace App\Modules\StandardObservationMaster\Exporters;

use App\Modules\ImportExport\Contracts\Exportable;
use App\Modules\StandardObservationMaster\Models\StandardObservationMaster;
use Illuminate\Database\Eloquent\Builder;

class StandardObservationExporter implements Exportable
{
    public function label(): string
    {
        return 'Standard Observations';
    }

    public function headers(): array
    {
        return ['ID', 'Name', 'Code', 'Active', 'Notes', 'Created At'];
    }

    public function query(): Builder
    {
        return StandardObservationMaster::query()->orderBy('name');
    }

    /**
     * @param  StandardObservationMaster  $model
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
        return 'standard-observations';
    }
}
