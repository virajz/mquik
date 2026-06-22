<?php

namespace App\Modules\ServiceSpecialistMaster\Exporters;

use App\Modules\ImportExport\Contracts\Exportable;
use App\Modules\ServiceSpecialistMaster\Models\ServiceSpecialistMaster;
use Illuminate\Database\Eloquent\Builder;

class ServiceSpecialistExporter implements Exportable
{
    public function label(): string
    {
        return 'Service Specialists';
    }

    public function headers(): array
    {
        return ['ID', 'Name', 'Code', 'Active', 'Notes', 'Created At'];
    }

    public function query(): Builder
    {
        return ServiceSpecialistMaster::query()->orderBy('name');
    }

    /**
     * @param  ServiceSpecialistMaster  $model
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
        return 'service-specialists';
    }
}
