<?php

namespace App\Modules\ReceiptCancellationReasonMaster\Exporters;

use App\Modules\ImportExport\Contracts\Exportable;
use App\Modules\ReceiptCancellationReasonMaster\Models\ReceiptCancellationReasonMaster;
use Illuminate\Database\Eloquent\Builder;

class ReceiptCancellationReasonExporter implements Exportable
{
    public function label(): string
    {
        return 'Receipt Cancellation Reasons';
    }

    public function headers(): array
    {
        return ['ID', 'Name', 'Code', 'Active', 'Notes', 'Created At'];
    }

    public function query(): Builder
    {
        return ReceiptCancellationReasonMaster::query()->orderBy('name');
    }

    /**
     * @param  ReceiptCancellationReasonMaster  $model
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
        return 'receipt-cancellation-reasons';
    }
}
