<?php

namespace App\Modules\DocumentRejectionReasonMaster\Exporters;

use App\Modules\DocumentRejectionReasonMaster\Models\DocumentRejectionReasonMaster;
use App\Modules\ImportExport\Contracts\Exportable;
use Illuminate\Database\Eloquent\Builder;

class DocumentRejectionReasonExporter implements Exportable
{
    public function label(): string
    {
        return 'Document Rejection Reasons';
    }

    public function headers(): array
    {
        return ['ID', 'Name', 'Code', 'Active', 'Notes', 'Created At'];
    }

    public function query(): Builder
    {
        return DocumentRejectionReasonMaster::query()->orderBy('name');
    }

    /**
     * @param  DocumentRejectionReasonMaster  $model
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
        return 'document-rejection-reasons';
    }
}
