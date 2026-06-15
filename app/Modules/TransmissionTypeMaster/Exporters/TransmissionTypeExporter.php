<?php

namespace App\Modules\TransmissionTypeMaster\Exporters;

use App\Modules\ImportExport\Contracts\Exportable;
use App\Modules\TransmissionTypeMaster\Models\TransmissionTypeMaster;
use Illuminate\Database\Eloquent\Builder;

class TransmissionTypeExporter implements Exportable
{
    public function label(): string
    {
        return 'Transmission Types';
    }

    public function headers(): array
    {
        return ['ID', 'Name', 'Code', 'Active', 'Notes', 'Created At'];
    }

    public function query(): Builder
    {
        return TransmissionTypeMaster::query()->orderBy('name');
    }

    /**
     * @param  TransmissionTypeMaster  $model
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
        return 'transmission-types';
    }
}
