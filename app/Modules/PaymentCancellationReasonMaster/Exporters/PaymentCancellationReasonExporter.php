<?php

namespace App\Modules\PaymentCancellationReasonMaster\Exporters;

use App\Modules\ImportExport\Contracts\Exportable;
use App\Modules\PaymentCancellationReasonMaster\Models\PaymentCancellationReasonMaster;
use Illuminate\Database\Eloquent\Builder;

class PaymentCancellationReasonExporter implements Exportable
{
    public function label(): string
    {
        return 'Payment Cancellation Reasons';
    }

    public function headers(): array
    {
        return ['ID', 'Name', 'Code', 'Active', 'Notes', 'Created At'];
    }

    public function query(): Builder
    {
        return PaymentCancellationReasonMaster::query()->orderBy('name');
    }

    /**
     * @param  PaymentCancellationReasonMaster  $model
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
        return 'payment-cancellation-reasons';
    }
}
