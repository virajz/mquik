<?php

namespace App\Modules\CustomerApprovalTypeMaster\Exporters;

use App\Modules\CustomerApprovalTypeMaster\Models\CustomerApprovalTypeMaster;
use App\Modules\ImportExport\Contracts\Exportable;
use Illuminate\Database\Eloquent\Builder;

class CustomerApprovalTypeExporter implements Exportable
{
    public function label(): string
    {
        return 'Customer Approval Types';
    }

    public function headers(): array
    {
        return ['ID', 'Name', 'Code', 'Active', 'Notes', 'Created At'];
    }

    public function query(): Builder
    {
        return CustomerApprovalTypeMaster::query()->orderBy('name');
    }

    /**
     * @param  CustomerApprovalTypeMaster  $model
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
        return 'customer-approval-types';
    }
}
