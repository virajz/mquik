<?php

namespace App\Modules\VehicleInventoryItemMaster\Exporters;

use App\Modules\ImportExport\Contracts\Exportable;
use App\Modules\VehicleInventoryItemMaster\Models\VehicleInventoryItemMaster;
use Illuminate\Database\Eloquent\Builder;

class VehicleInventoryItemExporter implements Exportable
{
    public function label(): string
    {
        return 'Vehicle Inventory Items';
    }

    public function headers(): array
    {
        return ['ID', 'Name', 'Code', 'Active', 'Notes', 'Created At'];
    }

    public function query(): Builder
    {
        return VehicleInventoryItemMaster::query()->orderBy('name');
    }

    /**
     * @param  VehicleInventoryItemMaster  $model
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
        return 'vehicle-inventory-items';
    }
}
