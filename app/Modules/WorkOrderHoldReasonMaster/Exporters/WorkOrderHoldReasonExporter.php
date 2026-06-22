<?php

namespace App\Modules\WorkOrderHoldReasonMaster\Exporters;

use App\Modules\ImportExport\Contracts\Exportable;
use App\Modules\WorkOrderHoldReasonMaster\Models\WorkOrderHoldReasonMaster;
use Illuminate\Database\Eloquent\Builder;

class WorkOrderHoldReasonExporter implements Exportable
{
    public function label(): string
    {
        return 'Work Order Hold Reasons';
    }

    public function headers(): array
    {
        return ['ID', 'Name', 'Code', 'Active', 'Notes', 'Created At'];
    }

    public function query(): Builder
    {
        return WorkOrderHoldReasonMaster::query()->orderBy('name');
    }

    /**
     * @param  WorkOrderHoldReasonMaster  $model
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
        return 'work-order-hold-reasons';
    }
}
