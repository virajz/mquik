<?php

namespace App\Modules\BookingChannelMaster\Exporters;

use App\Modules\BookingChannelMaster\Models\BookingChannelMaster;
use App\Modules\ImportExport\Contracts\Exportable;
use Illuminate\Database\Eloquent\Builder;

class BookingChannelExporter implements Exportable
{
    public function label(): string
    {
        return 'Booking Channels';
    }

    public function headers(): array
    {
        return ['ID', 'Name', 'Code', 'Active', 'Notes', 'Created At'];
    }

    public function query(): Builder
    {
        return BookingChannelMaster::query()->orderBy('name');
    }

    /**
     * @param  BookingChannelMaster  $model
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
        return 'booking-channels';
    }
}
