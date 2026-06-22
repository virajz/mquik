<?php

namespace App\Modules\DelayReasonMaster\Exporters;

use App\Modules\DelayReasonMaster\Models\DelayReasonMaster;
use App\Modules\ImportExport\Contracts\Exportable;
use Illuminate\Database\Eloquent\Builder;

class DelayReasonExporter implements Exportable
{
    public function label(): string
    {
        return 'Delay Reasons';
    }

    public function headers(): array
    {
        return ['ID', 'Name', 'Code', 'Active', 'Notes', 'Created At'];
    }

    public function query(): Builder
    {
        return DelayReasonMaster::query()->orderBy('name');
    }

    /**
     * @param  DelayReasonMaster  $model
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
        return 'delay-reasons';
    }
}
