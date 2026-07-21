<?php

namespace App\Modules\PendingReasonMaster\Exporters;

use App\Modules\ImportExport\Contracts\Exportable;
use App\Modules\PendingReasonMaster\Models\PendingReasonMaster;
use Illuminate\Database\Eloquent\Builder;

class PendingReasonExporter implements Exportable
{
    public function label(): string
    {
        return 'Pending Reasons';
    }

    public function headers(): array
    {
        return ['ID', 'Name', 'Code', 'Active', 'Notes', 'Created At'];
    }

    public function query(): Builder
    {
        return PendingReasonMaster::query()->orderBy('name');
    }

    /**
     * @param  PendingReasonMaster  $model
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
        return 'pending-reasons';
    }
}
