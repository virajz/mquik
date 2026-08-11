<?php

namespace App\Modules\VendorPurchaseOrder\Models;

use App\Concerns\Auditable;
use App\Concerns\Searchable;
use App\Modules\AdvancePayment\Models\AdvancePayment;
use App\Modules\EmployeeMaster\Models\EmployeeMaster;
use App\Modules\EstimateRevisionReasonMaster\Models\EstimateRevisionReasonMaster;
use App\Modules\JobCard\Models\JobCard;
use App\Modules\PriorityMaster\Models\PriorityMaster;
use App\Modules\VendorMaster\Models\VendorMaster;
use App\Modules\VendorPurchaseInquiry\Models\VendorPurchaseInquiry;
use App\Modules\VendorPurchaseOrder\Database\Factories\VendorPurchaseOrderFactory;
use App\Modules\VendorTypeMaster\Models\VendorTypeMaster;
use App\Modules\VpoApproval\Models\VpoApproval;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VendorPurchaseOrder extends Model
{
    use Auditable;
    use HasFactory;
    use Searchable;

    public const STATUS_PENDING = 'pending';

    public const STATUS_ACKNOWLEDGED = 'acknowledged';

    public const STATUS_DISPATCHED = 'dispatched';

    public const STATUS_DELIVERED = 'delivered';

    public const STATUS_CANCELLED = 'cancelled';

    public const ACK_PENDING = 'pending';

    public const ACK_ACCEPTED = 'accepted';

    public const ACK_REJECTED = 'rejected';

    protected $table = 'vendor_purchase_orders';

    protected $guarded = [];

    protected $casts = [
        'delivery_custom_days' => 'integer',
        'expected_delivery_date' => 'date',
        'consignment_date' => 'date',
    ];

    protected static array $searchableFields = ['po_no', 'consignment_no', 'notes', 'jobCard.job_card_no'];

    protected static function newFactory(): VendorPurchaseOrderFactory
    {
        return VendorPurchaseOrderFactory::new();
    }

    protected static function booted(): void
    {
        static::created(function (self $row) {
            if ($row->po_no === null) {
                $row->forceFill([
                    'po_no' => 'VPO-'.str_pad((string) $row->id, 5, '0', STR_PAD_LEFT),
                ])->saveQuietly();
            }
        });
    }

    /** @return array<string, string> */
    public static function statuses(): array
    {
        return [
            self::STATUS_PENDING => 'Pending',
            self::STATUS_ACKNOWLEDGED => 'Acknowledged',
            self::STATUS_DISPATCHED => 'Dispatched',
            self::STATUS_DELIVERED => 'Delivered',
            self::STATUS_CANCELLED => 'Cancelled',
        ];
    }

    /** @return array<string, string> */
    public static function acknowledgementStatuses(): array
    {
        return [
            self::ACK_PENDING => 'Pending',
            self::ACK_ACCEPTED => 'Accepted',
            self::ACK_REJECTED => 'Rejected',
        ];
    }

    /** @return array<string, string> */
    public static function poTypes(): array
    {
        return [
            'against_job_card' => 'Against Job Card Requirement',
            'stock_replenishment' => 'Stock Replenishment',
            'expense' => 'Expense',
            'odd_item' => 'Odd Item Purchase',
        ];
    }

    /** @return array<string, string> */
    public static function vendorCategories(): array
    {
        return [
            'preferred' => 'Preferred Vendor',
            'approved' => 'Approved Vendor',
            'backup' => 'Backup Vendor',
        ];
    }

    /** @return array<string, string> */
    public static function vendorRatingTypes(): array
    {
        return [
            'quality' => 'Quality',
            'price' => 'Price',
            'delivery' => 'Delivery',
            'support' => 'Support',
        ];
    }

    /** @return array<string, string> */
    public static function paymentTerms(): array
    {
        return [
            'advance' => 'Advance',
            'cod' => 'Cash on Delivery',
            'credit_7' => '7 Days Credit',
            'credit_15' => '15 Days Credit',
            'credit_30' => '30 Days Credit',
        ];
    }

    /** @return array<string, string> */
    public static function deliveryModes(): array
    {
        return [
            'pickup' => 'Self Pickup',
            'courier' => 'Courier',
            'transport' => 'Transport',
            'hand_delivery' => 'Hand Delivery',
        ];
    }

    /** @return array<string, string> */
    public static function deliveryCommitments(): array
    {
        return [
            'immediate' => 'Immediate',
            'same_day' => 'Same Day',
            'next_day' => 'Next Day',
            'two_three_days' => '2–3 Days',
            'custom' => 'Custom',
        ];
    }

    /** @return array<string, string> */
    public static function rejectionReasons(): array
    {
        return [
            'out_of_stock' => 'Out of Stock',
            'price_revised' => 'Price Revised',
            'moq_not_met' => 'MOQ Not Met',
            'credit_hold' => 'Credit Hold',
            'discontinued' => 'Part Discontinued',
            'other' => 'Other',
        ];
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(VendorMaster::class, 'vendor_id');
    }

    public function vendorType(): BelongsTo
    {
        return $this->belongsTo(VendorTypeMaster::class, 'vendor_type_id');
    }

    public function jobCard(): BelongsTo
    {
        return $this->belongsTo(JobCard::class, 'job_card_id');
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(EmployeeMaster::class, 'employee_id');
    }

    public function priority(): BelongsTo
    {
        return $this->belongsTo(PriorityMaster::class, 'priority_id');
    }

    public function inquiry(): BelongsTo
    {
        return $this->belongsTo(VendorPurchaseInquiry::class, 'vendor_purchase_inquiry_id');
    }

    public function approval(): BelongsTo
    {
        return $this->belongsTo(VpoApproval::class, 'vpo_approval_id');
    }

    public function advancePayment(): BelongsTo
    {
        return $this->belongsTo(AdvancePayment::class, 'advance_payment_id');
    }

    public function transportCompany(): BelongsTo
    {
        return $this->belongsTo(VendorMaster::class, 'transport_company_id');
    }

    public function cancellationReason(): BelongsTo
    {
        return $this->belongsTo(EstimateRevisionReasonMaster::class, 'cancellation_reason_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(VendorPurchaseOrderItem::class, 'vendor_purchase_order_id')->orderBy('sequence_no');
    }

    public function charges(): HasMany
    {
        return $this->hasMany(VendorPurchaseOrderCharge::class, 'vendor_purchase_order_id')->orderBy('sequence_no');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(VendorPurchaseOrderAttachment::class, 'vendor_purchase_order_id')->orderBy('sequence_no');
    }
}
