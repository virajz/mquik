<?php

namespace App\Modules\InspectionItemMaster\Exporters;

use App\Modules\ImportExport\Contracts\Exportable;
use App\Modules\InspectionItemMaster\Models\InspectionItemMaster;
use Illuminate\Database\Eloquent\Builder;

class InspectionItemExporter implements Exportable
{
    public function label(): string
    {
        return 'Inspection Items';
    }

    public function headers(): array
    {
        return ['ID', 'Name', 'Code', 'Group', 'Check Type', 'Measurement Unit', 'Active', 'Notes', 'Created At'];
    }

    public function query(): Builder
    {
        return InspectionItemMaster::query()->with('group:id,name')->orderBy('name');
    }

    /**
     * @param  InspectionItemMaster  $model
     */
    public function row(object $model): array
    {
        return [
            $model->id,
            $model->name,
            $model->code,
            $model->group?->name,
            $model->check_type,
            $model->measurement_unit,
            $model->is_active ? 'YES' : 'NO',
            $model->notes,
            $model->created_at?->toIso8601String(),
        ];
    }

    public function fileName(): string
    {
        return 'inspection-items';
    }
}
