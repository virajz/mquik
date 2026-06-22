<?php

namespace App\Modules\IpoCancellationReasonMaster\Exporters;

use App\Modules\ImportExport\Contracts\Exportable;
use App\Modules\IpoCancellationReasonMaster\Models\IpoCancellationReasonMaster;
use Illuminate\Database\Eloquent\Builder;

class IpoCancellationReasonExporter implements Exportable
{
    public function label(): string
    {
        return 'IPO Cancellation Reasons';
    }

    public function headers(): array
    {
        return ['ID', 'Name', 'Code', 'Active', 'Notes', 'Created At'];
    }

    public function query(): Builder
    {
        return IpoCancellationReasonMaster::query()->orderBy('name');
    }

    /**
     * @param  IpoCancellationReasonMaster  $model
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
        return 'ipo-cancellation-reasons';
    }
}
