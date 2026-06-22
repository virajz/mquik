<?php

namespace App\Modules\RackMaster\Exporters;

use App\Modules\ImportExport\Contracts\Exportable;
use App\Modules\RackMaster\Models\RackMaster;
use Illuminate\Database\Eloquent\Builder;

class RackExporter implements Exportable
{
    public function label(): string
    {
        return 'Racks';
    }

    public function headers(): array
    {
        return ['ID', 'Name', 'Code', 'Active', 'Notes', 'Created At'];
    }

    public function query(): Builder
    {
        return RackMaster::query()->orderBy('name');
    }

    /**
     * @param  RackMaster  $model
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
        return 'racks';
    }
}
