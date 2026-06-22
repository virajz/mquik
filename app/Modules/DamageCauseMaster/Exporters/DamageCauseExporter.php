<?php

namespace App\Modules\DamageCauseMaster\Exporters;

use App\Modules\DamageCauseMaster\Models\DamageCauseMaster;
use App\Modules\ImportExport\Contracts\Exportable;
use Illuminate\Database\Eloquent\Builder;

class DamageCauseExporter implements Exportable
{
    public function label(): string
    {
        return 'Damage Causes';
    }

    public function headers(): array
    {
        return ['ID', 'Name', 'Code', 'Active', 'Notes', 'Created At'];
    }

    public function query(): Builder
    {
        return DamageCauseMaster::query()->orderBy('name');
    }

    /**
     * @param  DamageCauseMaster  $model
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
        return 'damage-causes';
    }
}
