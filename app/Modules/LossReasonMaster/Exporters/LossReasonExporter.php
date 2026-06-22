<?php

namespace App\Modules\LossReasonMaster\Exporters;

use App\Modules\ImportExport\Contracts\Exportable;
use App\Modules\LossReasonMaster\Models\LossReasonMaster;
use Illuminate\Database\Eloquent\Builder;

class LossReasonExporter implements Exportable
{
    public function label(): string
    {
        return 'Loss Reasons';
    }

    public function headers(): array
    {
        return ['ID', 'Name', 'Code', 'Active', 'Notes', 'Created At'];
    }

    public function query(): Builder
    {
        return LossReasonMaster::query()->orderBy('name');
    }

    /**
     * @param  LossReasonMaster  $model
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
        return 'loss-reasons';
    }
}
