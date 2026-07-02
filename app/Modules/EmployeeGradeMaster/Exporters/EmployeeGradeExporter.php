<?php

namespace App\Modules\EmployeeGradeMaster\Exporters;

use App\Modules\EmployeeGradeMaster\Models\EmployeeGradeMaster;
use App\Modules\ImportExport\Contracts\Exportable;
use Illuminate\Database\Eloquent\Builder;

class EmployeeGradeExporter implements Exportable
{
    public function label(): string
    {
        return 'Employee Grades';
    }

    public function headers(): array
    {
        return ['ID', 'Name', 'Code', 'Active', 'Notes', 'Created At'];
    }

    public function query(): Builder
    {
        return EmployeeGradeMaster::query()->orderBy('name');
    }

    /**
     * @param  EmployeeGradeMaster  $model
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
        return 'employee-grades';
    }
}
