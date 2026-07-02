<?php

namespace App\Modules\SalesReturnReasonMaster\Exporters;

use App\Modules\ImportExport\Contracts\Exportable;
use App\Modules\SalesReturnReasonMaster\Models\SalesReturnReasonMaster;
use Illuminate\Database\Eloquent\Builder;

class SalesReturnReasonExporter implements Exportable
{
    public function label(): string
    {
        return 'Sales Return Reasons';
    }

    public function headers(): array
    {
        return ['ID', 'Name', 'Code', 'Active', 'Notes', 'Created At'];
    }

    public function query(): Builder
    {
        return SalesReturnReasonMaster::query()->orderBy('name');
    }

    /**
     * @param  SalesReturnReasonMaster  $model
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
        return 'sales-return-reasons';
    }
}
