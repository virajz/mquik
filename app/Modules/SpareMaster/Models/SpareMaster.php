<?php

namespace App\Modules\SpareMaster\Models;

use App\Concerns\Auditable;
use App\Concerns\Searchable;
use App\Modules\InventoryGroupMaster\Models\InventoryGroupMaster;
use App\Modules\PartTypeMaster\Models\PartTypeMaster;
use App\Modules\RackMaster\Models\RackMaster;
use App\Modules\SpareBrandMaster\Models\SpareBrandMaster;
use App\Modules\SpareMaster\Database\Factories\SpareMasterFactory;
use App\Modules\TaxMaster\Models\TaxMaster;
use App\Modules\UnitOfMeasureMaster\Models\UnitOfMeasureMaster;
use App\Modules\VehicleVariantMaster\Models\VehicleVariantMaster;
use App\Modules\VendorMaster\Models\VendorMaster;
use App\Modules\WorkshopDepartmentMaster\Models\WorkshopDepartmentMaster;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class SpareMaster extends Model
{
    use Auditable;
    use HasFactory;
    use Searchable;

    protected $table = 'spares';

    protected $guarded = [];

    protected $casts = [
        'is_active' => 'boolean',
        'is_tyre' => 'boolean',
        'rate_before_tax' => 'decimal:2',
        'mrp' => 'decimal:2',
        'min_qty' => 'decimal:2',
        'max_qty' => 'decimal:2',
    ];

    protected static array $searchableFields = ['name', 'spare_code', 'hsn_code', 'description'];

    protected static function newFactory(): SpareMasterFactory
    {
        return SpareMasterFactory::new();
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(SpareBrandMaster::class, 'spare_brand_id');
    }

    public function tax(): BelongsTo
    {
        return $this->belongsTo(TaxMaster::class, 'tax_id');
    }

    public function inventoryGroup(): BelongsTo
    {
        return $this->belongsTo(InventoryGroupMaster::class, 'inventory_group_id');
    }

    public function inventorySubGroup(): BelongsTo
    {
        return $this->belongsTo(InventoryGroupMaster::class, 'inventory_sub_group_id');
    }

    public function workshopDepartment(): BelongsTo
    {
        return $this->belongsTo(WorkshopDepartmentMaster::class, 'workshop_department_id');
    }

    public function uom(): BelongsTo
    {
        return $this->belongsTo(UnitOfMeasureMaster::class, 'uom_id');
    }

    public function partType(): BelongsTo
    {
        return $this->belongsTo(PartTypeMaster::class, 'part_type_id');
    }

    public function rack(): BelongsTo
    {
        return $this->belongsTo(RackMaster::class, 'rack_id');
    }

    public function vendors(): BelongsToMany
    {
        return $this->belongsToMany(
            VendorMaster::class,
            'spare_vendor',
            'spare_id',
            'vendor_id'
        )->withTimestamps();
    }

    public function vehicleVariants(): BelongsToMany
    {
        return $this->belongsToMany(
            VehicleVariantMaster::class,
            'spare_vehicle_variants',
            'spare_id',
            'vehicle_variant_id'
        )->withTimestamps();
    }

    /**
     * Live-computed price including tax — never stored to keep tax changes from
     * silently rotting old rows.
     */
    public function getRateInclTaxAttribute(): float
    {
        $rate = (float) $this->rate_before_tax;
        $tax = $this->tax;
        $pct = (float) (($tax?->gst_percent ?? 0) + ($tax?->cess_percent ?? 0));

        return round($rate * (1 + $pct / 100), 2);
    }
}
