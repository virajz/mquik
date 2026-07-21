<?php

namespace App\Modules\ParkingSlotMaster\Exporters;

use App\Modules\ImportExport\Contracts\Exportable;
use App\Modules\ParkingSlotMaster\Models\ParkingSlotMaster;
use Illuminate\Database\Eloquent\Builder;

class ParkingSlotExporter implements Exportable
{
    public function label(): string
    {
        return 'Parking Slots';
    }

    public function headers(): array
    {
        return ['ID', 'Name', 'Code', 'Active', 'Notes', 'Created At'];
    }

    public function query(): Builder
    {
        return ParkingSlotMaster::query()->orderBy('name');
    }

    /**
     * @param  ParkingSlotMaster  $model
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
        return 'parking-slots';
    }
}
