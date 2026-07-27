<?php

namespace App\Modules\VendorPurchaseInquiry\Models;

use App\Modules\ChargeTypeMaster\Models\ChargeTypeMaster;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * An additional charge on an RFQ — freight, P&F, transport, packing, handling, etc.
 */
class VendorPurchaseInquiryCharge extends Model
{
    protected $table = 'vendor_purchase_inquiry_charges';

    protected $guarded = [];

    protected $casts = [
        'amount' => 'decimal:2',
        'sequence_no' => 'integer',
    ];

    public function inquiry(): BelongsTo
    {
        return $this->belongsTo(VendorPurchaseInquiry::class, 'vendor_purchase_inquiry_id');
    }

    public function chargeType(): BelongsTo
    {
        return $this->belongsTo(ChargeTypeMaster::class, 'charge_type_id');
    }
}
