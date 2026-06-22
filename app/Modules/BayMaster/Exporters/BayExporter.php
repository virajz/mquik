<?php

namespace App\Modules\BayMaster\Exporters;

use App\Modules\BayMaster\Models\BayMaster;
use App\Modules\ImportExport\Contracts\Exportable;
use Illuminate\Database\Eloquent\Builder;

class BayExporter implements Exportable
{
    public function label(): string
    {
        return 'Bays';
    }

    public function headers(): array
    {
        return ['ID', 'Name', 'Code', 'Active', 'Notes', 'Created At'];
    }

    public function query(): Builder
    {
        return BayMaster::query()->orderBy('name');
    }

    /**
     * @param  BayMaster  $model
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
        return 'bays';
    }
}
