<?php

namespace App\Modules\JobCardCancelReasonMaster\Exporters;

use App\Modules\ImportExport\Contracts\Exportable;
use App\Modules\JobCardCancelReasonMaster\Models\JobCardCancelReasonMaster;
use Illuminate\Database\Eloquent\Builder;

class JobCardCancelReasonExporter implements Exportable
{
    public function label(): string
    {
        return 'Job Card Cancel Reasons';
    }

    public function headers(): array
    {
        return ['ID', 'Name', 'Code', 'Active', 'Notes', 'Created At'];
    }

    public function query(): Builder
    {
        return JobCardCancelReasonMaster::query()->orderBy('name');
    }

    /**
     * @param  JobCardCancelReasonMaster  $model
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
        return 'job-card-cancel-reasons';
    }
}
