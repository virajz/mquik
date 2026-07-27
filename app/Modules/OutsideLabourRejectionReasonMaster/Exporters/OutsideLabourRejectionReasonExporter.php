<?php

namespace App\Modules\OutsideLabourRejectionReasonMaster\Exporters;

use App\Modules\ImportExport\Contracts\Exportable;
use App\Modules\OutsideLabourRejectionReasonMaster\Models\OutsideLabourRejectionReasonMaster;
use Illuminate\Database\Eloquent\Builder;

class OutsideLabourRejectionReasonExporter implements Exportable
{
    public function label(): string
    {
        return 'OSL Rejection Reasons';
    }

    public function headers(): array
    {
        return ['ID', 'Name', 'Code', 'Active', 'Notes', 'Created At'];
    }

    public function query(): Builder
    {
        return OutsideLabourRejectionReasonMaster::query()->orderBy('name');
    }

    /**
     * @param  OutsideLabourRejectionReasonMaster  $model
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
        return 'outside-labour-rejection-reasons';
    }
}
