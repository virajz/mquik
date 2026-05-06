<?php

namespace App\Modules\InventoryGroupMaster\Exporters;

use App\Modules\ImportExport\Contracts\Exportable;
use App\Modules\InventoryGroupMaster\Models\InventoryGroupMaster;
use Illuminate\Database\Eloquent\Builder;

class InventoryGroupExporter implements Exportable
{
    public function label(): string
    {
        return 'Inventory Groups';
    }

    public function headers(): array
    {
        return ['ID', 'Name', 'Code', 'Parent', 'Active', 'Notes', 'Created At'];
    }

    public function query(): Builder
    {
        return InventoryGroupMaster::query()->with('parent:id,name')->orderBy('name');
    }

    /**
     * @param  InventoryGroupMaster  $model
     */
    public function row(object $model): array
    {
        return [
            $model->id,
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
        return 'inventory-groups';
    }
}
