<?php

namespace App\Modules\EmployeeCategoryMaster\Exporters;

use App\Modules\EmployeeCategoryMaster\Models\EmployeeCategoryMaster;
use App\Modules\ImportExport\Contracts\Exportable;
use Illuminate\Database\Eloquent\Builder;

class EmployeeCategoryExporter implements Exportable
{
    public function label(): string
    {
        return 'Employee Categories';
    }

    public function headers(): array
    {
        return ['ID', 'Name', 'Code', 'Active', 'Notes', 'Created At'];
    }

    public function query(): Builder
    {
        return EmployeeCategoryMaster::query()->orderBy('name');
    }

    /**
     * @param  EmployeeCategoryMaster  $model
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
        return 'employee-categories';
    }
}
