<?php

namespace App\Modules\InspectionItemGroupMaster\Exporters;

use App\Modules\ImportExport\Contracts\Exportable;
use App\Modules\InspectionItemGroupMaster\Models\InspectionItemGroupMaster;
use Illuminate\Database\Eloquent\Builder;

class InspectionItemGroupExporter implements Exportable
{
    public function label(): string
    {
        return 'Inspection Item Groups';
    }

    public function headers(): array
    {
        return ['ID', 'Name', 'Code', 'Active', 'Notes', 'Created At'];
    }

    public function query(): Builder
    {
        return InspectionItemGroupMaster::query()->orderBy('name');
    }

    /**
     * @param  InspectionItemGroupMaster  $model
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
        return 'inspection-item-groups';
    }
}
