<?php

namespace App\Modules\CreditNoteReasonMaster\Exporters;

use App\Modules\CreditNoteReasonMaster\Models\CreditNoteReasonMaster;
use App\Modules\ImportExport\Contracts\Exportable;
use Illuminate\Database\Eloquent\Builder;

class CreditNoteReasonExporter implements Exportable
{
    public function label(): string
    {
        return 'Credit Note Reasons';
    }

    public function headers(): array
    {
        return ['ID', 'Name', 'Code', 'Active', 'Notes', 'Created At'];
    }

    public function query(): Builder
    {
        return CreditNoteReasonMaster::query()->orderBy('name');
    }

    /**
     * @param  CreditNoteReasonMaster  $model
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
        return 'credit-note-reasons';
    }
}
