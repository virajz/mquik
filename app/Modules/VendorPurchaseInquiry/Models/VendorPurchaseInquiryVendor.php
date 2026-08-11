<?php

namespace App\Modules\VendorPurchaseInquiry\Models;

use App\Modules\VendorMaster\Models\VendorMaster;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One vendor an RFQ was sent to, and what they came back with.
 */
class VendorPurchaseInquiryVendor extends Model
{
    public const STATUS_AWAITING = 'awaiting';

    public const STATUS_QUOTED = 'quoted';

    public const STATUS_DECLINED = 'declined';

    public const STATUS_NO_RESPONSE = 'no_response';

    protected $table = 'vendor_purchase_inquiry_vendors';

    protected $guarded = [];

    protected $casts = [
        'sent_at' => 'datetime',
        'responded_at' => 'datetime',
        'quoted_total' => 'decimal:2',
        'lead_time_days' => 'integer',
        'is_selected' => 'boolean',
    ];

    /** @return array<string, string> */
    public static function responseStatuses(): array
    {
        return [
            self::STATUS_AWAITING => 'Awaiting Reply',
            self::STATUS_QUOTED => 'Quoted',
            self::STATUS_DECLINED => 'Declined',
            self::STATUS_NO_RESPONSE => 'No Response',
        ];
    }

    /** @return array<string, string> */
    public static function dispatchChannels(): array
    {
        return [
            'whatsapp' => 'WhatsApp',
            'email' => 'Email',
            'phone' => 'Phone',
            'portal' => 'Vendor Portal',
        ];
    }

    public function inquiry(): BelongsTo
    {
        return $this->belongsTo(VendorPurchaseInquiry::class, 'vendor_purchase_inquiry_id');
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(VendorMaster::class, 'vendor_id');
    }
}
