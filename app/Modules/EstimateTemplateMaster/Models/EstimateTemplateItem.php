<?php

namespace App\Modules\EstimateTemplateMaster\Models;

use App\Modules\HsnMaster\Models\HsnMaster;
use App\Modules\InventoryGroupMaster\Models\InventoryGroupMaster;
use App\Modules\LabourMaster\Models\LabourMaster;
use App\Modules\SpareMaster\Models\SpareMaster;
use App\Modules\TaxMaster\Models\TaxMaster;
use App\Modules\UnitOfMeasureMaster\Models\UnitOfMeasureMaster;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EstimateTemplateItem extends Model
{
    protected $table = 'estimate_template_items';

    protected $guarded = [];

    protected $casts = [
        'default_qty' => 'decimal:2',
        'unit_rate' => 'decimal:2',
    ];

    /** Line total before tax = qty × rate. */
    public function lineTotal(): float
    {
        return round((float) $this->default_qty * (float) $this->unit_rate, 2);
    }

    /** Tax amount from the line's tax slab. */
    public function taxAmount(): float
    {
        $pct = (float) ($this->tax?->gst_percent ?? 0);

        return round($this->lineTotal() * $pct / 100, 2);
    }

    /** Net sales amount = line total + tax. */
    public function netAmount(): float
    {
        return round($this->lineTotal() + $this->taxAmount(), 2);
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(EstimateTemplateMaster::class, 'estimate_template_id');
    }

    public function spare(): BelongsTo
    {
        return $this->belongsTo(SpareMaster::class, 'spare_id');
    }

    public function labour(): BelongsTo
    {
        return $this->belongsTo(LabourMaster::class, 'labour_id');
    }

    public function inventoryGroup(): BelongsTo
    {
        return $this->belongsTo(InventoryGroupMaster::class, 'inventory_group_id');
    }

    public function uom(): BelongsTo
    {
        return $this->belongsTo(UnitOfMeasureMaster::class, 'uom_id');
    }

    public function hsn(): BelongsTo
    {
        return $this->belongsTo(HsnMaster::class, 'hsn_id');
    }

    public function tax(): BelongsTo
    {
        return $this->belongsTo(TaxMaster::class, 'tax_id');
    }
}
