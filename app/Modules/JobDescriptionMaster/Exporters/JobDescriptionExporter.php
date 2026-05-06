<?php

namespace App\Modules\JobDescriptionMaster\Exporters;

use App\Modules\ImportExport\Contracts\Exportable;
use App\Modules\JobDescriptionMaster\Models\JobDescriptionMaster;
use Illuminate\Database\Eloquent\Builder;

class JobDescriptionExporter implements Exportable
{
    public function label(): string
    {
        return 'Job Descriptions';
    }

    public function headers(): array
    {
        return ['ID', 'Name', 'Code', 'Category', 'Service Type', 'Standard Hours', 'Active', 'Notes', 'Created At'];
    }

    public function query(): Builder
    {
        return JobDescriptionMaster::query()->with('serviceType:id,name')->orderBy('name');
    }

    /**
     * @param  JobDescriptionMaster  $model
     */
    public function row(object $model): array
    {
        return [
            $model->id,
            $model->name,
            $model->code,
            $model->category,
            $model->serviceType?->name,
            $model->standard_hours !== null ? number_format($model->standard_hours, 2, '.', '') : null,
            $model->is_active ? 'YES' : 'NO',
            $model->notes,
            $model->created_at?->toIso8601String(),
        ];
    }

    public function fileName(): string
    {
        return 'job-descriptions';
    }
}
