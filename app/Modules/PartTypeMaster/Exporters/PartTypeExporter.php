<?php

namespace App\Modules\PartTypeMaster\Exporters;

use App\Modules\ImportExport\Contracts\Exportable;
use App\Modules\PartTypeMaster\Models\PartTypeMaster;
use Illuminate\Database\Eloquent\Builder;

class PartTypeExporter implements Exportable
{
    public function label(): string
    {
        return 'Part Types';
    }

    public function headers(): array
    {
        return ['ID', 'Name', 'Code', 'Active', 'Notes', 'Created At'];
    }

    public function query(): Builder
    {
        return PartTypeMaster::query()->orderBy('name');
    }

    /**
     * @param  PartTypeMaster  $model
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
        return 'part-types';
    }
}
