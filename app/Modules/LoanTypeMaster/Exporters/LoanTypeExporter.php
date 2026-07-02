<?php

namespace App\Modules\LoanTypeMaster\Exporters;

use App\Modules\ImportExport\Contracts\Exportable;
use App\Modules\LoanTypeMaster\Models\LoanTypeMaster;
use Illuminate\Database\Eloquent\Builder;

class LoanTypeExporter implements Exportable
{
    public function label(): string
    {
        return 'Loan Types';
    }

    public function headers(): array
    {
        return ['ID', 'Name', 'Code', 'Active', 'Notes', 'Created At'];
    }

    public function query(): Builder
    {
        return LoanTypeMaster::query()->orderBy('name');
    }

    /**
     * @param  LoanTypeMaster  $model
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
        return 'loan-types';
    }
}
