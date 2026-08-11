<?php

namespace App\Modules\VendorPurchaseInquiry\Models;

use App\Modules\PartTypeMaster\Models\PartTypeMaster;
use App\Modules\SpareBrandMaster\Models\SpareBrandMaster;
use App\Modules\VendorMaster\Models\VendorMaster;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One priced offer against one requested part.
 *
 * A line can carry several: the same part quoted by different vendors, or the
 * same vendor quoting genuine against aftermarket. Exactly one is selected, and
 * that is the price the purchase order uses.
 */
class VendorPurchaseInquiryQuote extends Model
{
    public const AVAILABILITY_IN_STOCK = 'in_stock';

    public const AVAILABILITY_TO_ORDER = 'to_order';

    public const AVAILABILITY_NOT_AVAILABLE = 'not_available';

    protected $table = 'vendor_purchase_inquiry_quotes';

    protected $guarded = [];

    protected $casts = [
        'rate' => 'decimal:2',
        'discount_value' => 'decimal:2',
        'warranty_period_value' => 'integer',
        'lead_time_days' => 'integer',
        'is_selected' => 'boolean',
    ];

    /** @return array<string, string> */
    public static function availabilities(): array
    {
        return [
            self::AVAILABILITY_IN_STOCK => 'In Stock',
            self::AVAILABILITY_TO_ORDER => 'To Order',
            self::AVAILABILITY_NOT_AVAILABLE => 'Not Available',
        ];
    }

    /** Rate after the line discount — what the comparison should sort on. */
    public function netRate(): float
    {
        return max(0, (float) $this->rate - (float) $this->discount_value);
    }

    /** e.g. "12 months" — null when no warranty was offered. */
    public function warrantyLabel(): ?string
    {
        if (! $this->warranty_period_value) {
            return null;
        }

        $unit = $this->warranty_period_unit === 'day' ? 'day' : 'month';

        return $this->warranty_period_value.' '.str($unit)->plural((int) $this->warranty_period_value);
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(VendorPurchaseInquiryItem::class, 'vendor_purchase_inquiry_item_id');
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(VendorMaster::class, 'vendor_id');
    }

    public function partType(): BelongsTo
    {
        return $this->belongsTo(PartTypeMaster::class, 'part_type_id');
    }

    public function spareBrand(): BelongsTo
    {
        return $this->belongsTo(SpareBrandMaster::class, 'spare_brand_id');
    }
}
