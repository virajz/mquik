<?php

namespace App\Modules\LabourMaster\Models;

use App\Concerns\Auditable;
use App\Concerns\Searchable;
use App\Modules\HsnMaster\Models\HsnMaster;
use App\Modules\InventoryGroupMaster\Models\InventoryGroupMaster;
use App\Modules\LabourMaster\Database\Factories\LabourMasterFactory;
use App\Modules\TaxMaster\Models\TaxMaster;
use App\Modules\VehicleSegmentMaster\Models\VehicleSegmentMaster;
use App\Modules\WorkshopDepartmentMaster\Models\WorkshopDepartmentMaster;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LabourMaster extends Model
{
    use Auditable;
    use HasFactory;
    use Searchable;

    protected $table = 'labours';

    protected $guarded = [];

    protected $casts = [
        'is_active' => 'boolean',
        'is_osl' => 'boolean',
        'rate_before_tax' => 'decimal:2',
    ];

    protected static array $searchableFields = ['name', 'labour_code', 'description', 'hsn.code'];

    protected static function newFactory(): LabourMasterFactory
    {
        return LabourMasterFactory::new();
    }

    public function tax(): BelongsTo
    {
        return $this->belongsTo(TaxMaster::class, 'tax_id');
    }

    public function vehicleSegment(): BelongsTo
    {
        return $this->belongsTo(VehicleSegmentMaster::class, 'vehicle_segment_id');
    }

    public function workshopDepartment(): BelongsTo
    {
        return $this->belongsTo(WorkshopDepartmentMaster::class, 'workshop_department_id');
    }

    public function inventoryGroup(): BelongsTo
    {
        return $this->belongsTo(InventoryGroupMaster::class, 'inventory_group_id');
    }

    public function inventorySubGroup(): BelongsTo
    {
        return $this->belongsTo(InventoryGroupMaster::class, 'inventory_sub_group_id');
    }

    public function getRateInclTaxAttribute(): float
    {
        $rate = (float) $this->rate_before_tax;
        $tax = $this->tax;
        $pct = (float) (($tax?->gst_percent ?? 0) + ($tax?->cess_percent ?? 0));

        return round($rate * (1 + $pct / 100), 2);
    }

    /** SAC code for this labour operation. */
    public function hsn(): BelongsTo
    {
        return $this->belongsTo(HsnMaster::class, 'hsn_id');
    }
}
