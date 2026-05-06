<?php

namespace App\Modules\ConsumableDepartmentMaster\Exporters;

use App\Modules\ConsumableDepartmentMaster\Models\ConsumableDepartmentMaster;
use App\Modules\ImportExport\Contracts\Exportable;
use Illuminate\Database\Eloquent\Builder;

class ConsumableDepartmentExporter implements Exportable
{
    public function label(): string
    {
        return 'Consumable Departments';
    }

    public function headers(): array
    {
        return ['ID', 'Name', 'Code', 'Active', 'Notes', 'Created At'];
    }

    public function query(): Builder
    {
        return ConsumableDepartmentMaster::query()->orderBy('name');
    }

    /**
     * @param  ConsumableDepartmentMaster  $model
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
        return 'consumable-departments';
    }
}
