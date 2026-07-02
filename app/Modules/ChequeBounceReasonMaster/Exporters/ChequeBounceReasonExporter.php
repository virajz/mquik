<?php

namespace App\Modules\ChequeBounceReasonMaster\Exporters;

use App\Modules\ChequeBounceReasonMaster\Models\ChequeBounceReasonMaster;
use App\Modules\ImportExport\Contracts\Exportable;
use Illuminate\Database\Eloquent\Builder;

class ChequeBounceReasonExporter implements Exportable
{
    public function label(): string
    {
        return 'Cheque Bounce Reasons';
    }

    public function headers(): array
    {
        return ['ID', 'Name', 'Code', 'Active', 'Notes', 'Created At'];
    }

    public function query(): Builder
    {
        return ChequeBounceReasonMaster::query()->orderBy('name');
    }

    /**
     * @param  ChequeBounceReasonMaster  $model
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
        return 'cheque-bounce-reasons';
    }
}
