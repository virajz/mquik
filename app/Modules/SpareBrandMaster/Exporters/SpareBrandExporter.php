<?php

namespace App\Modules\SpareBrandMaster\Exporters;

use App\Modules\ImportExport\Contracts\Exportable;
use App\Modules\SpareBrandMaster\Models\SpareBrandMaster;
use Illuminate\Database\Eloquent\Builder;

class SpareBrandExporter implements Exportable
{
    public function label(): string
    {
        return 'Spare Brands';
    }

    public function headers(): array
    {
        return ['ID', 'Name', 'Code', 'Active', 'Notes', 'Created At'];
    }

    public function query(): Builder
    {
        return SpareBrandMaster::query()->orderBy('name');
    }

    /**
     * @param  SpareBrandMaster  $model
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
        return 'spare-brands';
    }
}
