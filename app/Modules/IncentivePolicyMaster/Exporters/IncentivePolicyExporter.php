<?php

namespace App\Modules\IncentivePolicyMaster\Exporters;

use App\Modules\ImportExport\Contracts\Exportable;
use App\Modules\IncentivePolicyMaster\Models\IncentivePolicyMaster;
use Illuminate\Database\Eloquent\Builder;

class IncentivePolicyExporter implements Exportable
{
    public function label(): string
    {
        return 'Incentive Policies';
    }

    public function headers(): array
    {
        return ['ID', 'Name', 'Code', 'Active', 'Notes', 'Created At'];
    }

    public function query(): Builder
    {
        return IncentivePolicyMaster::query()->orderBy('name');
    }

    /**
     * @param  IncentivePolicyMaster  $model
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
        return 'incentive-policies';
    }
}
