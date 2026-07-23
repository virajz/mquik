<?php

namespace App\Modules\InsuranceCompanyMaster\Exporters;

use App\Modules\ImportExport\Contracts\Exportable;
use App\Modules\InsuranceCompanyMaster\Models\InsuranceCompanyMaster;
use Illuminate\Database\Eloquent\Builder;

class InsuranceCompanyExporter implements Exportable
{
    public function label(): string
    {
        return 'Insurance Companies';
    }

    public function headers(): array
    {
        return [
            'ID',
            'Name',
            'Short Name',
            'GSTIN',
            'Contact Person',
            'Phone',
            'Email',
            'Address',
            'Notes',
            'Active',
            'Created At',
        ];
    }

    public function query(): Builder
    {
        return InsuranceCompanyMaster::query()->orderBy('name');
    }

    /**
     * @param  InsuranceCompanyMaster  $model
     */
    public function row(object $model): array
    {
        return [
            $model->id,
            $model->name,
            $model->short_name,
            $model->gstin,
            $model->contact_person,
            $model->phone,
            $model->email,
            $model->address,
            $model->notes,
            $model->is_active ? 'YES' : 'NO',
            $model->created_at?->toIso8601String(),
        ];
    }

    public function fileName(): string
    {
        return 'insurance-companies';
    }
}
