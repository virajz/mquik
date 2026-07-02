<?php

namespace App\Modules\InvoiceCancellationReasonMaster\Exporters;

use App\Modules\ImportExport\Contracts\Exportable;
use App\Modules\InvoiceCancellationReasonMaster\Models\InvoiceCancellationReasonMaster;
use Illuminate\Database\Eloquent\Builder;

class InvoiceCancellationReasonExporter implements Exportable
{
    public function label(): string
    {
        return 'Invoice Cancellation Reasons';
    }

    public function headers(): array
    {
        return ['ID', 'Name', 'Code', 'Active', 'Notes', 'Created At'];
    }

    public function query(): Builder
    {
        return InvoiceCancellationReasonMaster::query()->orderBy('name');
    }

    /**
     * @param  InvoiceCancellationReasonMaster  $model
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
        return 'invoice-cancellation-reasons';
    }
}
