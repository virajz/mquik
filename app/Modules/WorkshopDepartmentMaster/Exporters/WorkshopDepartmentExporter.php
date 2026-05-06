<?php

namespace App\Modules\WorkshopDepartmentMaster\Exporters;

use App\Modules\ImportExport\Contracts\Exportable;
use App\Modules\WorkshopDepartmentMaster\Models\WorkshopDepartmentMaster;
use Illuminate\Database\Eloquent\Builder;

class WorkshopDepartmentExporter implements Exportable
{
    public function label(): string
    {
        return 'Workshop Departments';
    }

    public function headers(): array
    {
        return ['ID', 'Name', 'Code', 'Active', 'Notes', 'Created At'];
    }

    public function query(): Builder
    {
        return WorkshopDepartmentMaster::query()->orderBy('name');
    }

    /**
     * @param  WorkshopDepartmentMaster  $model
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
        return 'workshop-departments';
    }
}
