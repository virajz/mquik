<?php

namespace App\Modules\ReceiptDifferenceReasonMaster\Exporters;

use App\Modules\ImportExport\Contracts\Exportable;
use App\Modules\ReceiptDifferenceReasonMaster\Models\ReceiptDifferenceReasonMaster;
use Illuminate\Database\Eloquent\Builder;

class ReceiptDifferenceReasonExporter implements Exportable
{
    public function label(): string
    {
        return 'Receipt Difference Reasons';
    }

    public function headers(): array
    {
        return ['ID', 'Name', 'Code', 'Active', 'Notes', 'Created At'];
    }

    public function query(): Builder
    {
        return ReceiptDifferenceReasonMaster::query()->orderBy('name');
    }

    /**
     * @param  ReceiptDifferenceReasonMaster  $model
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
        return 'receipt-difference-reasons';
    }
}
