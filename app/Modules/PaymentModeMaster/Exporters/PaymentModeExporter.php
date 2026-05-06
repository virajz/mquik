<?php

namespace App\Modules\PaymentModeMaster\Exporters;

use App\Modules\ImportExport\Contracts\Exportable;
use App\Modules\PaymentModeMaster\Models\PaymentModeMaster;
use Illuminate\Database\Eloquent\Builder;

class PaymentModeExporter implements Exportable
{
    public function label(): string
    {
        return 'Payment Modes';
    }

    public function headers(): array
    {
        return ['ID', 'Name', 'Code', 'Active', 'Notes', 'Created At'];
    }

    public function query(): Builder
    {
        return PaymentModeMaster::query()->orderBy('name');
    }

    /**
     * @param  PaymentModeMaster  $model
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
        return 'payment-modes';
    }
}
