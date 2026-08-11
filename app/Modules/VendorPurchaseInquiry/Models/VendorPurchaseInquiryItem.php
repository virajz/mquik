<?php

namespace App\Modules\VendorPurchaseInquiry\Models;

use App\Modules\HsnMaster\Models\HsnMaster;
use App\Modules\PartTypeMaster\Models\PartTypeMaster;
use App\Modules\SpareBrandMaster\Models\SpareBrandMaster;
use App\Modules\SpareMaster\Models\SpareMaster;
use App\Modules\TaxMaster\Models\TaxMaster;
use App\Modules\UnitOfMeasureMaster\Models\UnitOfMeasureMaster;
use App\Modules\VehicleVariantMaster\Models\VehicleVariantMaster;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * One requested part on an RFQ, carrying the vendor's quote for that line
 * (rate, discount, warranty, lead time, availability).
 */
class VendorPurchaseInquiryItem extends Model
{
    protected $table = 'vendor_purchase_inquiry_items';

    protected $guarded = [];

    protected $casts = [
        'quantity' => 'decimal:2',
        'quoted_rate' => 'decimal:2',
        'discount_value' => 'decimal:2',
        'warranty_period_value' => 'integer',
        'lead_time_days' => 'integer',
        'sequence_no' => 'integer',
    ];

    /** @return array<string, string> */
    public static function discountTypes(): array
    {
        return [
            'line' => 'Line Discount',
            'scheme' => 'Scheme Discount',
            'cash' => 'Cash Discount',
        ];
    }

    /** @return array<string, string> */
    public static function warrantyTypes(): array
    {
        return [
            'no_warranty' => 'No Warranty',
            'vendor' => 'Vendor Warranty',
            'manufacturer' => 'Manufacturer Warranty',
        ];
    }

    /** @return array<string, string> */
    public static function warrantyUnits(): array
    {
        return [
            'month' => 'Months',
            'day' => 'Days',
        ];
    }

    /** @return array<string, string> */
    public static function stockStatuses(): array
    {
        return [
            'partially_available' => 'Partially Available',
            'fully_available' => 'Fully Available',
            'not_available' => 'Not Available',
        ];
    }

    /** @return array<string, string> */
    public static function alternativeOptions(): array
    {
        return [
            'primary' => 'Primary Part',
            'alternate_part' => 'Alternate Part',
            'alternate_brand' => 'Alternate Brand',
        ];
    }

    /**
     * Competing offers for this part — across vendors and part grades.
     *
     * @return HasMany<VendorPurchaseInquiryQuote>
     */
    public function quotes(): HasMany
    {
        return $this->hasMany(VendorPurchaseInquiryQuote::class, 'vendor_purchase_inquiry_item_id')
            ->orderBy('id');
    }

    /** The quote the purchase order should use, if one has been picked. */
    public function selectedQuote(): ?VendorPurchaseInquiryQuote
    {
        return $this->quotes->firstWhere('is_selected', true);
    }

    public function inquiry(): BelongsTo
    {
        return $this->belongsTo(VendorPurchaseInquiry::class, 'vendor_purchase_inquiry_id');
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
