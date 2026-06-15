<?php

namespace App\Modules\ServicePackageTypeMaster\Exporters;

use App\Modules\ImportExport\Contracts\Exportable;
use App\Modules\ServicePackageTypeMaster\Models\ServicePackageTypeMaster;
use Illuminate\Database\Eloquent\Builder;

class ServicePackageTypeExporter implements Exportable
{
    public function label(): string
    {
        return 'Service Package Types';
    }

    public function headers(): array
    {
        return ['ID', 'Name', 'Code', 'Active', 'Notes', 'Created At'];
    }

    public function query(): Builder
    {
        return ServicePackageTypeMaster::query()->orderBy('name');
    }

    /**
     * @param  ServicePackageTypeMaster  $model
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
        return 'service-package-types';
    }
}
