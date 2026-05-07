<?php

namespace App\Modules\ChecklistGroupMaster\Exporters;

use App\Modules\ChecklistGroupMaster\Models\ChecklistGroupMaster;
use App\Modules\ImportExport\Contracts\Exportable;
use Illuminate\Database\Eloquent\Builder;

class ChecklistGroupExporter implements Exportable
{
    public function label(): string
    {
        return 'Checklist Groups';
    }

    public function headers(): array
    {
        return ['ID', 'Name', 'Code', 'Active', 'Notes', 'Created At'];
    }

    public function query(): Builder
    {
        return ChecklistGroupMaster::query()->orderBy('name');
    }

    /**
     * @param  ChecklistGroupMaster  $model
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
        return 'checklist-groups';
    }
}
