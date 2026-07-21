<?php

namespace App\Modules\TimeSlotMaster\Exporters;

use App\Modules\ImportExport\Contracts\Exportable;
use App\Modules\TimeSlotMaster\Models\TimeSlotMaster;
use Illuminate\Database\Eloquent\Builder;

class TimeSlotExporter implements Exportable
{
    public function label(): string
    {
        return 'Time Slots';
    }

    public function headers(): array
    {
        return ['ID', 'Name', 'Code', 'Start Time', 'End Time', 'Max Vehicles', 'Buffer (min)', 'Active', 'Notes', 'Created At'];
    }

    public function query(): Builder
    {
        return TimeSlotMaster::query()->orderBy('slot_start_time');
    }

    /**
     * @param  TimeSlotMaster  $model
     */
    public function row(object $model): array
    {
        return [
            $model->id,
            $model->name,
            $model->code,
            $model->slot_start_time,
            $model->slot_end_time,
            $model->max_vehicles_per_slot,
            $model->buffer_minutes,
            $model->is_active ? 'YES' : 'NO',
            $model->notes,
            $model->created_at?->toIso8601String(),
        ];
    }

    public function fileName(): string
    {
        return 'time-slots';
    }
}
