<?php

namespace App\Modules\GstTypeMaster\Exporters;

use App\Modules\GstTypeMaster\Models\GstTypeMaster;
use App\Modules\ImportExport\Contracts\Exportable;
use Illuminate\Database\Eloquent\Builder;

class GstTypeExporter implements Exportable
{
    public function label(): string
    {
        return 'GST Types';
    }

    public function headers(): array
    {
        return ['ID', 'Name', 'Code', 'Active', 'Notes', 'Created At'];
    }

    public function query(): Builder
    {
        return GstTypeMaster::query()->orderBy('name');
    }

    /**
     * @param  GstTypeMaster  $model
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
        return 'gst-types';
    }
}
