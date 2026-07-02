<?php

namespace App\Modules\LeaveTypeMaster\Exporters;

use App\Modules\ImportExport\Contracts\Exportable;
use App\Modules\LeaveTypeMaster\Models\LeaveTypeMaster;
use Illuminate\Database\Eloquent\Builder;

class LeaveTypeExporter implements Exportable
{
    public function label(): string
    {
        return 'Leave Types';
    }

    public function headers(): array
    {
        return ['ID', 'Name', 'Code', 'Active', 'Notes', 'Created At'];
    }

    public function query(): Builder
    {
        return LeaveTypeMaster::query()->orderBy('name');
    }

    /**
     * @param  LeaveTypeMaster  $model
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
        return 'leave-types';
    }
}
