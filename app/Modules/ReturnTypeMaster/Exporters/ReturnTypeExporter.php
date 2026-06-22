<?php

namespace App\Modules\ReturnTypeMaster\Exporters;

use App\Modules\ImportExport\Contracts\Exportable;
use App\Modules\ReturnTypeMaster\Models\ReturnTypeMaster;
use Illuminate\Database\Eloquent\Builder;

class ReturnTypeExporter implements Exportable
{
    public function label(): string
    {
        return 'Return Types';
    }

    public function headers(): array
    {
        return ['ID', 'Name', 'Code', 'Active', 'Notes', 'Created At'];
    }

    public function query(): Builder
    {
        return ReturnTypeMaster::query()->orderBy('name');
    }

    /**
     * @param  ReturnTypeMaster  $model
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
        return 'return-types';
    }
}
