<?php

namespace App\Modules\VpoCancelRequest\Models;

use App\Modules\HsnMaster\Models\HsnMaster;
use App\Modules\SpareBrandMaster\Models\SpareBrandMaster;
use App\Modules\SpareMaster\Models\SpareMaster;
use App\Modules\TaxMaster\Models\TaxMaster;
use App\Modules\UnitOfMeasureMaster\Models\UnitOfMeasureMaster;
use App\Modules\VehicleVariantMaster\Models\VehicleVariantMaster;
use App\Modules\VendorPurchaseOrder\Models\VendorPurchaseOrder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One cancellation line — the PO / job card / spare being cancelled, and how
 * much of it (quantity_to_cancel for a partial or qty-reduction request).
 */
class VpoCancelRequestItem extends Model
{
    protected $table = 'vpo_cancel_request_items';

    protected $guarded = [];

    protected $casts = [
        'quantity' => 'decimal:2',
        'quantity_to_cancel' => 'decimal:2',
        'rate' => 'decimal:2',
        'sequence_no' => 'integer',
    ];

    public function request(): BelongsTo
    {
        return $this->belongsTo(VpoCancelRequest::class, 'vpo_cancel_request_id');
    }

    public function purchaseOrder(): BelongsTo
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
