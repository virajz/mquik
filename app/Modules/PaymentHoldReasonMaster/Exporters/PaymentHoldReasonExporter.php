<?php

namespace App\Modules\PaymentHoldReasonMaster\Exporters;

use App\Modules\ImportExport\Contracts\Exportable;
use App\Modules\PaymentHoldReasonMaster\Models\PaymentHoldReasonMaster;
use Illuminate\Database\Eloquent\Builder;

class PaymentHoldReasonExporter implements Exportable
{
    public function label(): string
    {
        return 'Payment Hold Reasons';
    }

    public function headers(): array
    {
        return ['ID', 'Name', 'Code', 'Active', 'Notes', 'Created At'];
    }

    public function query(): Builder
    {
        return PaymentHoldReasonMaster::query()->orderBy('name');
    }

    /**
     * @param  PaymentHoldReasonMaster  $model
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
        return 'payment-hold-reasons';
    }
}
