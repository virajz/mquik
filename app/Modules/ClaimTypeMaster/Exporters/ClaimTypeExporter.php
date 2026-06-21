<?php

namespace App\Modules\ClaimTypeMaster\Exporters;

use App\Modules\ClaimTypeMaster\Models\ClaimTypeMaster;
use App\Modules\ImportExport\Contracts\Exportable;
use Illuminate\Database\Eloquent\Builder;

class ClaimTypeExporter implements Exportable
{
    public function label(): string
    {
        return 'Claim Types';
    }

    public function headers(): array
    {
        return ['ID', 'Name', 'Code', 'Active', 'Notes', 'Created At'];
    }

    public function query(): Builder
    {
        return ClaimTypeMaster::query()->orderBy('name');
    }

    /**
     * @param  ClaimTypeMaster  $model
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
        return 'claim-types';
    }
}
