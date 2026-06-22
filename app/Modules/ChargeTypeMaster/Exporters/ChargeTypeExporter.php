<?php

namespace App\Modules\ChargeTypeMaster\Exporters;

use App\Modules\ChargeTypeMaster\Models\ChargeTypeMaster;
use App\Modules\ImportExport\Contracts\Exportable;
use Illuminate\Database\Eloquent\Builder;

class ChargeTypeExporter implements Exportable
{
    public function label(): string
    {
        return 'Charge Types';
    }

    public function headers(): array
    {
        return ['ID', 'Name', 'Code', 'Active', 'Notes', 'Created At'];
    }

    public function query(): Builder
    {
        return ChargeTypeMaster::query()->orderBy('name');
    }

    /**
     * @param  ChargeTypeMaster  $model
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
        return 'charge-types';
    }
}
