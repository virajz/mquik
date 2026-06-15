<?php

namespace App\Modules\DamageTypeMaster\Exporters;

use App\Modules\DamageTypeMaster\Models\DamageTypeMaster;
use App\Modules\ImportExport\Contracts\Exportable;
use Illuminate\Database\Eloquent\Builder;

class DamageTypeExporter implements Exportable
{
    public function label(): string
    {
        return 'Damage Types';
    }

    public function headers(): array
    {
        return ['ID', 'Name', 'Code', 'Active', 'Notes', 'Created At'];
    }

    public function query(): Builder
    {
        return DamageTypeMaster::query()->orderBy('name');
    }

    /**
     * @param  DamageTypeMaster  $model
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
        return 'damage-types';
    }
}
