<?php

namespace App\Modules\InsurancePolicyTypeMaster\Exporters;

use App\Modules\ImportExport\Contracts\Exportable;
use App\Modules\InsurancePolicyTypeMaster\Models\InsurancePolicyTypeMaster;
use Illuminate\Database\Eloquent\Builder;

class InsurancePolicyTypeExporter implements Exportable
{
    public function label(): string
    {
        return 'Insurance Policy Types';
    }

    public function headers(): array
    {
        return ['ID', 'Name', 'Code', 'Active', 'Notes', 'Created At'];
    }

    public function query(): Builder
    {
        return InsurancePolicyTypeMaster::query()->orderBy('name');
    }

    /**
     * @param  InsurancePolicyTypeMaster  $model
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
        return 'insurance-policy-types';
    }
}
