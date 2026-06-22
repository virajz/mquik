<?php

namespace App\Modules\ReworkReasonMaster\Exporters;

use App\Modules\ImportExport\Contracts\Exportable;
use App\Modules\ReworkReasonMaster\Models\ReworkReasonMaster;
use Illuminate\Database\Eloquent\Builder;

class ReworkReasonExporter implements Exportable
{
    public function label(): string
    {
        return 'Rework Reasons';
    }

    public function headers(): array
    {
        return ['ID', 'Name', 'Code', 'Active', 'Notes', 'Created At'];
    }

    public function query(): Builder
    {
        return ReworkReasonMaster::query()->orderBy('name');
    }

    /**
     * @param  ReworkReasonMaster  $model
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
        return 'rework-reasons';
    }
}
