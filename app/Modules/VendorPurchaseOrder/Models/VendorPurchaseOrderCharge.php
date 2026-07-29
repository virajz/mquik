<?php

namespace App\Modules\VendorPurchaseOrder\Models;

use App\Modules\ChargeTypeMaster\Models\ChargeTypeMaster;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * An additional charge on a VPO — freight, P&F, transport, packing, handling, etc.
 */
class VendorPurchaseOrderCharge extends Model
{
    protected $table = 'vendor_purchase_order_charges';

    protected $guarded = [];

    protected $casts = ['amount' => 'decimal:2', 'sequence_no' => 'integer'];

    public function order(): BelongsTo
    {
        return $this->belongsTo(VendorPurchaseOrder::class, 'vendor_purchase_order_id');
    }

    public function chargeType(): BelongsTo
    {
        return $this->belongsTo(ChargeTypeMaster::class, 'charge_type_id');
    }
}
