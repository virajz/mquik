<?php

namespace App\Modules\ServiceIntervalMaster\Exporters;

use App\Modules\ImportExport\Contracts\Exportable;
use App\Modules\ServiceIntervalMaster\Models\ServiceIntervalMaster;
use Illuminate\Database\Eloquent\Builder;

class ServiceIntervalExporter implements Exportable
{
    public function label(): string
    {
        return 'Service Intervals';
    }

    public function headers(): array
    {
        return ['ID', 'Service', 'Every (months)', 'Every (km)', 'Active', 'Notes', 'Created At'];
    }

    public function query(): Builder
    {
        return ServiceIntervalMaster::query()->orderBy('name');
    }

    /**
     * @param  ServiceIntervalMaster  $model
     */
    public function row(object $model): array
    {
        return [
            $model->id,
            $model->name,
            $model->interval_months,
            $model->interval_km,
            $model->is_active ? 'YES' : 'NO',
            $model->description,
            $model->created_at?->toIso8601String(),
        ];
    }

    public function fileName(): string
    {
        return 'service-intervals';
    }
}
