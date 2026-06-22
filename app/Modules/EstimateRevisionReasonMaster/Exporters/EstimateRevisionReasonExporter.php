<?php

namespace App\Modules\EstimateRevisionReasonMaster\Exporters;

use App\Modules\EstimateRevisionReasonMaster\Models\EstimateRevisionReasonMaster;
use App\Modules\ImportExport\Contracts\Exportable;
use Illuminate\Database\Eloquent\Builder;

class EstimateRevisionReasonExporter implements Exportable
{
    public function label(): string
    {
        return 'Estimate Revision Reasons';
    }

    public function headers(): array
    {
        return ['ID', 'Name', 'Code', 'Active', 'Notes', 'Created At'];
    }

    public function query(): Builder
    {
        return EstimateRevisionReasonMaster::query()->orderBy('name');
    }

    /**
     * @param  EstimateRevisionReasonMaster  $model
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
        return 'estimate-revision-reasons';
    }
}
