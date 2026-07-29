<?php

namespace App\Modules\VendorPurchaseOrder\Models;

use App\Modules\HsnMaster\Models\HsnMaster;
use App\Modules\PartTypeMaster\Models\PartTypeMaster;
use App\Modules\SpareBrandMaster\Models\SpareBrandMaster;
use App\Modules\SpareMaster\Models\SpareMaster;
use App\Modules\TaxMaster\Models\TaxMaster;
use App\Modules\UnitOfMeasureMaster\Models\UnitOfMeasureMaster;
use App\Modules\VehicleVariantMaster\Models\VehicleVariantMaster;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One ordered part on a VPO — the agreed rate, discount, warranty and tax.
 */
class VendorPurchaseOrderItem extends Model
{
    protected $table = 'vendor_purchase_order_items';

    protected $guarded = [];

    protected $casts = [
        'quantity' => 'decimal:2',
        'rate' => 'decimal:2',
        'discount_value' => 'decimal:2',
        'closing_stock' => 'decimal:2',
        'warranty_period_value' => 'integer',
        'lead_time_days' => 'integer',
        'sequence_no' => 'integer',
    ];

    /** @return array<string, string> */
    public static function discountTypes(): array
    {
        return ['line' => 'Line Discount', 'scheme' => 'Scheme Discount', 'cash' => 'Cash Discount'];
    }

    /** @return array<string, string> */
    public static function warrantyTypes(): array
    {
        return ['no_warranty' => 'No Warranty', 'vendor' => 'Vendor Warranty', 'manufacturer' => 'Manufacturer Warranty'];
    }

    /** @return array<string, string> */
    public static function warrantyUnits(): array
    {
        return ['month' => 'Months', 'day' => 'Days'];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(VendorPurchaseOrder::class, 'vendor_purchase_order_id');
    }

    public function spare(): BelongsTo
    {
        return $this->belongsTo(SpareMaster::class, 'spare_id');
    }

    public function spareBrand(): BelongsTo
    {
        return $this->belongsTo(SpareBrandMaster::class, 'spare_brand_id');
    }

    public function partType(): BelongsTo
    {
        return $this->belongsTo(PartTypeMaster::class, 'part_type_id');
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

    public function vehicleVariant(): BelongsTo
    {
        return $this->belongsTo(VehicleVariantMaster::class, 'vehicle_variant_id');
    }
}
