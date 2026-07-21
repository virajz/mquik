<?php

namespace App\Modules\PriorityMaster\Exporters;

use App\Modules\ImportExport\Contracts\Exportable;
use App\Modules\PriorityMaster\Models\PriorityMaster;
use Illuminate\Database\Eloquent\Builder;

class PriorityExporter implements Exportable
{
    public function label(): string
    {
        return 'Priorities';
    }

    public function headers(): array
    {
        return ['ID', 'Name', 'Code', 'Order', 'Applies To', 'Active', 'Notes', 'Created At'];
    }

    public function query(): Builder
    {
        return PriorityMaster::query()->orderBy('sort_order')->orderBy('name');
    }

    /**
     * @param  PriorityMaster  $model
     */
    public function row(object $model): array
    {
        return [
            $model->id,
            $model->name,
            $model->code,
            $model->sort_order,
            $model->applies_to,
            $model->is_active ? 'YES' : 'NO',
            $model->notes,
            $model->created_at?->toIso8601String(),
        ];
    }

    public function fileName(): string
    {
        return 'priorities';
    }
}
