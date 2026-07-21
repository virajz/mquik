<?php

namespace App\Modules\PickupDropOptionMaster\Exporters;

use App\Modules\ImportExport\Contracts\Exportable;
use App\Modules\PickupDropOptionMaster\Models\PickupDropOptionMaster;
use Illuminate\Database\Eloquent\Builder;

class PickupDropOptionExporter implements Exportable
{
    public function label(): string
    {
        return 'Pickup/Drop Options';
    }

    public function headers(): array
    {
        return ['ID', 'Name', 'Code', 'Involves Pickup', 'Involves Drop', 'Active', 'Notes', 'Created At'];
    }

    public function query(): Builder
    {
        return PickupDropOptionMaster::query()->orderBy('name');
    }

    /**
     * @param  PickupDropOptionMaster  $model
     */
    public function row(object $model): array
    {
        return [
            $model->id,
            $model->name,
            $model->code,
            $model->involves_pickup ? 'YES' : 'NO',
            $model->involves_drop ? 'YES' : 'NO',
            $model->is_active ? 'YES' : 'NO',
            $model->notes,
            $model->created_at?->toIso8601String(),
        ];
    }

    public function fileName(): string
    {
        return 'pickup-drop-options';
    }
}
