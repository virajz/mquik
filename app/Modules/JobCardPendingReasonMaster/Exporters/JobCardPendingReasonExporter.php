<?php

namespace App\Modules\JobCardPendingReasonMaster\Exporters;

use App\Modules\ImportExport\Contracts\Exportable;
use App\Modules\JobCardPendingReasonMaster\Models\JobCardPendingReasonMaster;
use Illuminate\Database\Eloquent\Builder;

class JobCardPendingReasonExporter implements Exportable
{
    public function label(): string
    {
        return 'Job Card Pending Reasons';
    }

    public function headers(): array
    {
        return ['ID', 'Name', 'Code', 'Active', 'Notes', 'Created At'];
    }

    public function query(): Builder
    {
        return JobCardPendingReasonMaster::query()->orderBy('name');
    }

    /**
     * @param  JobCardPendingReasonMaster  $model
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
        return 'job-card-pending-reasons';
    }
}
