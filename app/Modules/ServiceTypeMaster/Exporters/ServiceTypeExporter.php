<?php

namespace App\Modules\ServiceTypeMaster\Exporters;

use App\Modules\ImportExport\Contracts\Exportable;
use App\Modules\ServiceTypeMaster\Models\ServiceTypeMaster;
use Illuminate\Database\Eloquent\Builder;

class ServiceTypeExporter implements Exportable
{
    public function label(): string
    {
        return 'Service Types';
    }

    public function headers(): array
    {
        return ['ID', 'Name', 'Code', 'Workshop Department', 'Requires Advisor', 'Active', 'Notes', 'Created At'];
    }

    public function query(): Builder
    {
        return ServiceTypeMaster::query()->with('workshopDepartment:id,name')->orderBy('name');
    }

    /**
     * @param  ServiceTypeMaster  $model
     */
    public function row(object $model): array
    {
        return [
            $model->id,
            $model->name,
            $model->code,
            $model->workshopDepartment?->name,
            $model->requires_advisor ? 'YES' : 'NO',
            $model->is_active ? 'YES' : 'NO',
            $model->notes,
            $model->created_at?->toIso8601String(),
        ];
    }

    public function fileName(): string
    {
        return 'service-types';
    }
}
