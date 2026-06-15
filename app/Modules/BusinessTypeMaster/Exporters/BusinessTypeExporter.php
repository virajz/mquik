<?php

namespace App\Modules\BusinessTypeMaster\Exporters;

use App\Modules\BusinessTypeMaster\Models\BusinessTypeMaster;
use App\Modules\ImportExport\Contracts\Exportable;
use Illuminate\Database\Eloquent\Builder;

class BusinessTypeExporter implements Exportable
{
    public function label(): string
    {
        return 'Customer Types';
    }

    public function headers(): array
    {
        return ['ID', 'Name', 'Code', 'Active', 'Notes', 'Created At'];
    }

    public function query(): Builder
    {
        return BusinessTypeMaster::query()->orderBy('name');
    }

    /**
     * @param  BusinessTypeMaster  $model
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
        return 'business-types';
    }
}
