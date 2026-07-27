<?php

namespace App\Modules\IpiRejectionReasonMaster\Exporters;

use App\Modules\ImportExport\Contracts\Exportable;
use App\Modules\IpiRejectionReasonMaster\Models\IpiRejectionReasonMaster;
use Illuminate\Database\Eloquent\Builder;

class IpiRejectionReasonExporter implements Exportable
{
    public function label(): string
    {
        return 'IPI Rejection Reasons';
    }

    public function headers(): array
    {
        return ['ID', 'Name', 'Code', 'Active', 'Notes', 'Created At'];
    }

    public function query(): Builder
    {
        return IpiRejectionReasonMaster::query()->orderBy('name');
    }

    /**
     * @param  IpiRejectionReasonMaster  $model
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
        return 'ipi-rejection-reasons';
    }
}
