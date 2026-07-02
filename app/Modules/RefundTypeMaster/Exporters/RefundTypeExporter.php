<?php

namespace App\Modules\RefundTypeMaster\Exporters;

use App\Modules\ImportExport\Contracts\Exportable;
use App\Modules\RefundTypeMaster\Models\RefundTypeMaster;
use Illuminate\Database\Eloquent\Builder;

class RefundTypeExporter implements Exportable
{
    public function label(): string
    {
        return 'Refund Types';
    }

    public function headers(): array
    {
        return ['ID', 'Name', 'Code', 'Active', 'Notes', 'Created At'];
    }

    public function query(): Builder
    {
        return RefundTypeMaster::query()->orderBy('name');
    }

    /**
     * @param  RefundTypeMaster  $model
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
        return 'refund-types';
    }
}
