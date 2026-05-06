<?php

namespace App\Modules\RegionMaster\Exporters;

use App\Modules\ImportExport\Contracts\Exportable;
use App\Modules\RegionMaster\Models\RegionMaster;
use Illuminate\Database\Eloquent\Builder;

class RegionExporter implements Exportable
{
    public function label(): string
    {
        return 'Regions';
    }

    public function headers(): array
    {
        return ['ID', 'Kind', 'Name', 'Code', 'Parent', 'Active', 'Notes', 'Created At'];
    }

    public function query(): Builder
    {
        return RegionMaster::query()->with('parent:id,name')->orderBy('kind')->orderBy('name');
    }

    /**
     * @param  RegionMaster  $model
     */
    public function row(object $model): array
    {
        return [
            $model->id,
            $model->kind,
            $model->name,
            $model->code,
            $model->parent?->name,
            $model->is_active ? 'YES' : 'NO',
            $model->notes,
            $model->created_at?->toIso8601String(),
        ];
    }

    public function fileName(): string
    {
        return 'regions';
    }
}
