<?php

namespace App\Modules\LossTypeMaster\Exporters;

use App\Modules\ImportExport\Contracts\Exportable;
use App\Modules\LossTypeMaster\Models\LossTypeMaster;
use Illuminate\Database\Eloquent\Builder;

class LossTypeExporter implements Exportable
{
    public function label(): string
    {
        return 'Loss Types';
    }

    public function headers(): array
    {
        return ['ID', 'Name', 'Code', 'Active', 'Notes', 'Created At'];
    }

    public function query(): Builder
    {
        return LossTypeMaster::query()->orderBy('name');
    }

    /**
     * @param  LossTypeMaster  $model
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
        return 'loss-types';
    }
}
