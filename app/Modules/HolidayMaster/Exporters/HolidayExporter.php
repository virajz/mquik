<?php

namespace App\Modules\HolidayMaster\Exporters;

use App\Modules\HolidayMaster\Models\HolidayMaster;
use App\Modules\ImportExport\Contracts\Exportable;
use Illuminate\Database\Eloquent\Builder;

class HolidayExporter implements Exportable
{
    public function label(): string
    {
        return 'Holidays';
    }

    public function headers(): array
    {
        return ['ID', 'Name', 'Code', 'Active', 'Notes', 'Created At'];
    }

    public function query(): Builder
    {
        return HolidayMaster::query()->orderBy('name');
    }

    /**
     * @param  HolidayMaster  $model
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
        return 'holidays';
    }
}
