<?php

namespace App\Modules\VpoCancelRequest\Models;

use App\Concerns\Auditable;
use App\Concerns\Searchable;
use App\Modules\EmployeeMaster\Models\EmployeeMaster;
use App\Modules\FollowUpModeMaster\Models\FollowUpModeMaster;
use App\Modules\PriorityMaster\Models\PriorityMaster;
use App\Modules\VendorMaster\Models\VendorMaster;
use App\Modules\VendorPurchaseOrder\Models\VendorPurchaseOrder;
use App\Modules\VpoCancelRequest\Database\Factories\VpoCancelRequestFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VpoCancelRequest extends Model
{
    use Auditable;
    use HasFactory;
    use Searchable;

    public const STATUS_REQUESTED = 'requested_to_vendor';

    public const STATUS_REVIEWING = 'vendor_reviewing';

    public const STATUS_ACCEPTED = 'vendor_accepted';

    public const STATUS_REJECTED = 'vendor_rejected';

    public const STATUS_PARTIALLY_ACCEPTED = 'partially_accepted';

    public const STATUS_FULLY_ACCEPTED = 'fully_accepted';

    public const STATUS_CANCELLED = 'cancelled';

    protected $table = 'vpo_cancel_requests';

    protected $guarded = [];

    protected $casts = ['cancellation_charge' => 'decimal:2'];

    protected static array $searchableFields = ['request_no', 'notes', 'purchaseOrder.po_no'];

    protected static function newFactory(): VpoCancelRequestFactory
    {
        return VpoCancelRequestFactory::new();
    }

    protected static function booted(): void
    {
        static::created(function (self $row) {
            if ($row->request_no === null) {
                $row->forceFill(['request_no' => 'VCR-'.str_pad((string) $row->id, 5, '0', STR_PAD_LEFT)])->saveQuietly();
            }
        });
    }

    /** @return array<string, string> */
    public static function statuses(): array
    {
        return [
            self::STATUS_REQUESTED => 'Requested to Vendor',
            self::STATUS_REVIEWING => 'Vendor Reviewing',
            self::STATUS_ACCEPTED => 'Vendor Accepted',
            self::STATUS_REJECTED => 'Vendor Rejected',
            self::STATUS_PARTIALLY_ACCEPTED => 'Partially Accepted',
            self::STATUS_FULLY_ACCEPTED => 'Fully Accepted',
            self::STATUS_CANCELLED => 'Cancelled',
        ];
    }

    /** @return array<string, string> */
    public static function requestTypes(): array
    {
        return [
            'full' => 'Full Purchase Order Cancellation',
            'partial_item' => 'Partial Item Cancellation',
            'quantity_reduction' => 'Quantity Reduction',
        ];
    }

    /** @return array<string, string> */
    public static function cancellationReasons(): array
    {
        return [
            'wrong_part' => 'Wrong Part Ordered',
            'duplicate_po' => 'Duplicate Purchase Order',
            'same_part_available' => 'Same Part Available',
            'alternate_part_available' => 'Alternate Part Available',
            'customer_declined' => 'Customer Declined Order',
            'vehicle_sold' => 'Vehicle Already Sold',
        ];
    }

    /** @return array<string, string> */
    public static function vendorCategories(): array
    {
        return ['preferred' => 'Preferred Vendor', 'approved' => 'Approved Vendor', 'backup' => 'Backup Vendor'];
    }

    /** @return array<string, string> */
    public static function cancellationTerms(): array
    {
        return [
            'no_charge' => 'No Charge',
            'fixed_charge' => 'Fixed Charge',
            'percentage_charge' => 'Percentage Charge of PO Amt',
        ];
    }

    /** @return array<string, string> */
    public static function advancePaymentStatuses(): array
    {
        return [
            'no_advance' => 'No Advance Paid',
            'advance_paid' => 'Advance Paid',
            'refund_requested' => 'Refund Requested',
            'refund_adjusted' => 'Refund Adjusted',
            'refund_next_order' => 'Refund Adjusted in Next Order',
            'refund_received' => 'Refund Received',
        ];
    }

    /** @return array<string, string> */
    public static function holdReasons(): array
    {
        return [
            'dispatch_verification_pending' => 'Dispatch Verification Pending',
            'advance_settlement_pending' => 'Advance Settlement Pending',
        ];
    }

    /** Vendor's reason the order can no longer be cancelled — it's already moving. @return array<string, string> */
    public static function rejectionReasons(): array
    {
        return [
            'packed' => 'Packed',
            'picking_started' => 'Picking Started',
            'in_transit' => 'In Transit',
            'already_dispatched' => 'Already Dispatched',
        ];
    }

    /** @return array<string, string> */
    public static function vendorRatingTypes(): array
    {
        return [
            'quality' => 'Quality Product',
            'price' => 'Reasonable Price',
            'delivery' => 'Ontime Delivery',
            'support' => 'Handhold Support',
        ];
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(VendorMaster::class, 'vendor_id');
    }

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(VendorPurchaseOrder::class, 'vendor_purchase_order_id');
    }

    public function storeIncharge(): BelongsTo
    {
        return $this->belongsTo(EmployeeMaster::class, 'store_incharge_id');
    }

    public function priority(): BelongsTo
    {
        return $this->belongsTo(PriorityMaster::class, 'priority_id');
    }

    public function followUpMode(): BelongsTo
    {
        return $this->belongsTo(FollowUpModeMaster::class, 'follow_up_mode_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(VpoCancelRequestItem::class, 'vpo_cancel_request_id')->orderBy('sequence_no');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(VpoCancelRequestAttachment::class, 'vpo_cancel_request_id')->orderBy('sequence_no');
    }
}
