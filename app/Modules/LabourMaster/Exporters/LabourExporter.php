<?php

namespace App\Modules\LabourMaster\Exporters;

use App\Modules\ImportExport\Contracts\Exportable;
use App\Modules\LabourMaster\Models\LabourMaster;
use Illuminate\Database\Eloquent\Builder;

class LabourExporter implements Exportable
{
    public function label(): string
    {
        return 'Labour';
    }

    public function headers(): array
    {
        return [
            'ID', 'Name', 'Code', 'Description', 'HSN/SAC', 'Vehicle Segment',
            'Tax', 'Rate Before Tax', 'Rate Incl Tax',
            'Department', 'Inventory Group', 'Sub Group',
            'OSL', 'Remark', 'Active', 'Created At',
        ];
    }

    public function query(): Builder
    {
        return LabourMaster::query()
            ->with(['vehicleSegment:id,name', 'tax:id,name,gst_percent,cess_percent', 'workshopDepartment:id,name', 'inventoryGroup:id,name', 'inventorySubGroup:id,name'])
            ->orderBy('name');
    }

    /**
     * @param  LabourMaster  $model
     */
    public function row(object $model): array
    {
        return [
            $model->id,
            $model->name,
            $model->labour_code,
            $model->description,
            $model->hsn_sac_code,
            $model->vehicleSegment?->name,
            $model->tax?->name,
            number_format((float) $model->rate_before_tax, 2, '.', ''),
            number_format($model->rate_incl_tax, 2, '.', ''),
            $model->workshopDepartment?->name,
            $model->inventoryGroup?->name,
            $model->inventorySubGroup?->name,
            $model->is_osl ? 'YES' : 'NO',
            $model->remark,
            $model->is_active ? 'YES' : 'NO',
            $model->created_at?->toIso8601String(),
        ];
    }

    public function fileName(): string
    {
        return 'labour';
    }
}
