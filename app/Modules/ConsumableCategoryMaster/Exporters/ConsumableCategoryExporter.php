<?php

namespace App\Modules\ConsumableCategoryMaster\Exporters;

use App\Modules\ConsumableCategoryMaster\Models\ConsumableCategoryMaster;
use App\Modules\ImportExport\Contracts\Exportable;
use Illuminate\Database\Eloquent\Builder;

class ConsumableCategoryExporter implements Exportable
{
    public function label(): string
    {
        return 'Consumable Categories';
    }

    public function headers(): array
    {
        return ['ID', 'Name', 'Code', 'Active', 'Notes', 'Created At'];
    }

    public function query(): Builder
    {
        return ConsumableCategoryMaster::query()->orderBy('name');
    }

    /**
     * @param  ConsumableCategoryMaster  $model
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
        return 'consumable-categories';
    }
}
