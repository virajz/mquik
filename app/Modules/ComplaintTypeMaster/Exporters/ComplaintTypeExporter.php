<?php

namespace App\Modules\ComplaintTypeMaster\Exporters;

use App\Modules\ComplaintTypeMaster\Models\ComplaintTypeMaster;
use App\Modules\ImportExport\Contracts\Exportable;
use Illuminate\Database\Eloquent\Builder;

class ComplaintTypeExporter implements Exportable
{
    public function label(): string
    {
        return 'Complaint Types';
    }

    public function headers(): array
    {
        return ['ID', 'Name', 'Code', 'Active', 'Notes', 'Created At'];
    }

    public function query(): Builder
    {
        return ComplaintTypeMaster::query()->orderBy('name');
    }

    /**
     * @param  ComplaintTypeMaster  $model
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
        return 'complaint-types';
    }
}
