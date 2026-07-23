<?php

namespace App\Modules\SpareMaster\Exporters;

use App\Modules\ImportExport\Contracts\Exportable;
use App\Modules\SpareMaster\Models\SpareMaster;
use Illuminate\Database\Eloquent\Builder;

class SpareExporter implements Exportable
{
    public function label(): string
    {
        return 'Spares';
    }

    public function headers(): array
    {
        return [
            'ID', 'Name', 'Part No.', 'Description', 'HSN Code', 'Brand',
            'Tax', 'Rate Before Tax', 'Rate Incl Tax',
            'Inventory Group', 'Sub Group', 'Department', 'UoM',
            'MRP', 'Min Qty', 'Max Qty', 'Barcode Type', 'Godown',
            'Part Type', 'Tyre Dimension', 'Rim Size', 'LI-SI', 'Tread Pattern',
            'Remark', 'Active', 'Created At',
        ];
    }

    public function query(): Builder
    {
        return SpareMaster::query()
            ->with(['brand:id,name', 'tax:id,name,gst_percent,cess_percent', 'inventoryGroup:id,name', 'inventorySubGroup:id,name', 'workshopDepartment:id,name', 'uom:id,name,code'])
            ->orderBy('name');
    }

    /**
     * @param  SpareMaster  $model
     */
    public function row(object $model): array
    {
        return [
            $model->id,
            $model->name,
            $model->spare_code,
            $model->description,
            $model->hsn_code,
            $model->brand?->name,
            $model->tax?->name,
            number_format((float) $model->rate_before_tax, 2, '.', ''),
            $model->mrp === null ? null : number_format((float) $model->mrp, 2, '.', ''),
            number_format($model->rate_incl_tax, 2, '.', ''),
            $model->inventoryGroup?->name,
            $model->inventorySubGroup?->name,
            $model->workshopDepartment?->name,
            $model->uom?->name,
            number_format((float) $model->min_qty, 2, '.', ''),
            number_format((float) $model->max_qty, 2, '.', ''),
            $model->barcode_type,
            $model->location,
            SpareMaster::spareTypes()[$model->spare_type] ?? $model->spare_type,
            $model->tyre_dimension,
            $model->rim_size,
            $model->load_speed_index,
            $model->tread_pattern,
            $model->remark,
            $model->is_active ? 'YES' : 'NO',
            $model->created_at?->toIso8601String(),
        ];
    }

    public function fileName(): string
    {
        return 'spares';
    }
}
