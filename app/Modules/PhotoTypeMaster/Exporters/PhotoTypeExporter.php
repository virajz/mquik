<?php

namespace App\Modules\PhotoTypeMaster\Exporters;

use App\Modules\ImportExport\Contracts\Exportable;
use App\Modules\PhotoTypeMaster\Models\PhotoTypeMaster;
use Illuminate\Database\Eloquent\Builder;

class PhotoTypeExporter implements Exportable
{
    public function label(): string
    {
        return 'Photo Types';
    }

    public function headers(): array
    {
        return ['ID', 'Name', 'Code', 'Tab / Group', 'Order', 'Active', 'Notes', 'Created At'];
    }

    public function query(): Builder
    {
        return PhotoTypeMaster::query()->orderBy('group')->orderBy('sort_order')->orderBy('name');
    }

    /**
     * @param  PhotoTypeMaster  $model
     */
    public function row(object $model): array
    {
        return [
            $model->id,
            $model->name,
            $model->code,
            $model->group,
            $model->sort_order,
            $model->is_active ? 'YES' : 'NO',
            $model->notes,
            $model->created_at?->toIso8601String(),
        ];
    }

    public function fileName(): string
    {
        return 'photo-types';
    }
}
