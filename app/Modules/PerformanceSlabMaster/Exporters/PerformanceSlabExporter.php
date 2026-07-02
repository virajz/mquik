<?php

namespace App\Modules\PerformanceSlabMaster\Exporters;

use App\Modules\ImportExport\Contracts\Exportable;
use App\Modules\PerformanceSlabMaster\Models\PerformanceSlabMaster;
use Illuminate\Database\Eloquent\Builder;

class PerformanceSlabExporter implements Exportable
{
    public function label(): string
    {
        return 'Performance Slabs';
    }

    public function headers(): array
    {
        return ['ID', 'Name', 'Code', 'Active', 'Notes', 'Created At'];
    }

    public function query(): Builder
    {
        return PerformanceSlabMaster::query()->orderBy('name');
    }

    /**
     * @param  PerformanceSlabMaster  $model
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
        return 'performance-slabs';
    }
}
