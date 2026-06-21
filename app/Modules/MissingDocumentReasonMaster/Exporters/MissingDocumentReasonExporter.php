<?php

namespace App\Modules\MissingDocumentReasonMaster\Exporters;

use App\Modules\ImportExport\Contracts\Exportable;
use App\Modules\MissingDocumentReasonMaster\Models\MissingDocumentReasonMaster;
use Illuminate\Database\Eloquent\Builder;

class MissingDocumentReasonExporter implements Exportable
{
    public function label(): string
    {
        return 'Missing Document Reasons';
    }

    public function headers(): array
    {
        return ['ID', 'Name', 'Code', 'Active', 'Notes', 'Created At'];
    }

    public function query(): Builder
    {
        return MissingDocumentReasonMaster::query()->orderBy('name');
    }

    /**
     * @param  MissingDocumentReasonMaster  $model
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
        return 'missing-document-reasons';
    }
}
