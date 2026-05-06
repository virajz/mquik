<?php

namespace App\Modules\DepartmentMaster\Exporters;

use App\Modules\DepartmentMaster\Models\DepartmentMaster;
use App\Modules\ImportExport\Contracts\Exportable;
use Illuminate\Database\Eloquent\Builder;

class DepartmentExporter implements Exportable
{
    public function label(): string
    {
        return 'Departments';
    }

    public function headers(): array
    {
        return ['ID', 'Name', 'Code', 'Active', 'Notes', 'Created At'];
    }

    public function query(): Builder
    {
        return DepartmentMaster::query()->orderBy('name');
    }

    /**
     * @param  DepartmentMaster  $model
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
        return 'departments';
    }
}
