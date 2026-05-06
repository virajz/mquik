<?php

namespace App\Modules\DesignationMaster\Exporters;

use App\Modules\DesignationMaster\Models\DesignationMaster;
use App\Modules\ImportExport\Contracts\Exportable;
use Illuminate\Database\Eloquent\Builder;

class DesignationExporter implements Exportable
{
    public function label(): string
    {
        return 'Designations';
    }

    public function headers(): array
    {
        return ['ID', 'Name', 'Code', 'Active', 'Notes', 'Created At'];
    }

    public function query(): Builder
    {
        return DesignationMaster::query()->orderBy('name');
    }

    /**
     * @param  DesignationMaster  $model
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
        return 'designations';
    }
}
