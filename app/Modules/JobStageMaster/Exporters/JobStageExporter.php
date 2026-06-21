<?php

namespace App\Modules\JobStageMaster\Exporters;

use App\Modules\ImportExport\Contracts\Exportable;
use App\Modules\JobStageMaster\Models\JobStageMaster;
use Illuminate\Database\Eloquent\Builder;

class JobStageExporter implements Exportable
{
    public function label(): string
    {
        return 'Job Stages';
    }

    public function headers(): array
    {
        return ['ID', 'Name', 'Code', 'Track', 'Order', 'Active', 'Notes', 'Created At'];
    }

    public function query(): Builder
    {
        return JobStageMaster::query()->orderBy('track')->orderBy('sort_order')->orderBy('name');
    }

    /**
     * @param  JobStageMaster  $model
     */
    public function row(object $model): array
    {
        return [
            $model->id,
            $model->name,
            $model->code,
            $model->track,
            $model->sort_order,
            $model->is_active ? 'YES' : 'NO',
            $model->notes,
            $model->created_at?->toIso8601String(),
        ];
    }

    public function fileName(): string
    {
        return 'job-stages';
    }
}
