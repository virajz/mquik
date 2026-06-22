<?php

namespace App\Modules\ChallanRejectionReasonMaster\Exporters;

use App\Modules\ChallanRejectionReasonMaster\Models\ChallanRejectionReasonMaster;
use App\Modules\ImportExport\Contracts\Exportable;
use Illuminate\Database\Eloquent\Builder;

class ChallanRejectionReasonExporter implements Exportable
{
    public function label(): string
    {
        return 'Challan Rejection Reasons';
    }

    public function headers(): array
    {
        return ['ID', 'Name', 'Code', 'Active', 'Notes', 'Created At'];
    }

    public function query(): Builder
    {
        return ChallanRejectionReasonMaster::query()->orderBy('name');
    }

    /**
     * @param  ChallanRejectionReasonMaster  $model
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
        return 'challan-rejection-reasons';
    }
}
