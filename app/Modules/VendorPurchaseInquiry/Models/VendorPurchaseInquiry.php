<?php

namespace App\Modules\VendorPurchaseInquiry\Models;

use App\Concerns\Auditable;
use App\Concerns\Searchable;
use App\Modules\EmployeeMaster\Models\EmployeeMaster;
use App\Modules\EstimateRevisionReasonMaster\Models\EstimateRevisionReasonMaster;
use App\Modules\InternalPartsInquiry\Models\InternalPartsInquiry;
use App\Modules\JobCard\Models\JobCard;
use App\Modules\PriorityMaster\Models\PriorityMaster;
use App\Modules\VendorMaster\Models\VendorMaster;
use App\Modules\VendorPurchaseInquiry\Database\Factories\VendorPurchaseInquiryFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VendorPurchaseInquiry extends Model
{
    use Auditable;
    use HasFactory;
    use Searchable;

    public const STATUS_PENDING = 'pending';

    public const STATUS_IN_PROGRESS = 'in_progress';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_CANCELLED = 'cancelled';

    protected $table = 'vendor_purchase_inquiries';

    protected $guarded = [];

    protected $casts = [
        'tat_custom_days' => 'integer',
    ];

    protected static array $searchableFields = ['vpi_no', 'notes'];

    protected static function newFactory(): VendorPurchaseInquiryFactory
    {
        return VendorPurchaseInquiryFactory::new();
    }

    protected static function booted(): void
    {
        static::created(function (self $row) {
            if ($row->vpi_no === null) {
                $row->forceFill([
                    'vpi_no' => 'VPI-'.str_pad((string) $row->id, 5, '0', STR_PAD_LEFT),
                ])->saveQuietly();
            }
        });
    }

    /** @return array<string, string> */
    public static function statuses(): array
    {
        return [
            self::STATUS_PENDING => 'Pending',
            self::STATUS_IN_PROGRESS => 'In Progress',
            self::STATUS_COMPLETED => 'Completed',
            self::STATUS_CANCELLED => 'Cancelled',
        ];
    }

    /** @return array<string, string> */
    public static function inquiryTypes(): array
    {
        return [
            'against_job_card' => 'Against Job Card Requirement',
            'stock_replenishment' => 'Stock Replenishment',
            'expense' => 'Expense',
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

    /** Which dimension the vendor is being rated on for this inquiry. @return array<string, string> */
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

    /** The criterion used to compare competing vendor quotes. @return array<string, string> */
    public static function comparisonParameters(): array
    {
        return [
            'price' => 'Price',
            'discount' => 'Discount',
            'lead_time' => 'Lead Time',
            'delivery_on_time' => 'Delivery on Time',
            'brand' => 'Brand',
            'landed_cost' => 'Total Landed Cost',
            'warranty' => 'Warranty',
            'payment_terms' => 'Payment Terms',
        ];
    }

    /** @return array<string, string> */
    public static function approvalAuthorities(): array
    {
        return [
            'service_advisor' => 'Service Advisor',
            'store_manager' => 'Store Manager',
            'workshop_manager' => 'Workshop Manager',
            'owner' => 'Owner',
            'admin' => 'Admin',
        ];
    }

    /** @return array<string, string> */
    public static function tatOptions(): array
    {
        return [
            'immediate' => 'Immediate',
            'same_day' => 'Same Day',
            'next_day' => 'Next Day',
            'two_three_days' => '2–3 Days',
            'custom' => 'Custom',
        ];
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(VendorMaster::class, 'vendor_id');
    }

    public function jobCard(): BelongsTo
    {
        return $this->belongsTo(JobCard::class, 'job_card_id');
    }

    public function internalPartsInquiry(): BelongsTo
    {
        return $this->belongsTo(InternalPartsInquiry::class, 'internal_parts_inquiry_id');
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(EmployeeMaster::class, 'employee_id');
    }

    public function priority(): BelongsTo
    {
        return $this->belongsTo(PriorityMaster::class, 'priority_id');
    }

    public function revisionReason(): BelongsTo
    {
        return $this->belongsTo(EstimateRevisionReasonMaster::class, 'revision_reason_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(VendorPurchaseInquiryItem::class, 'vendor_purchase_inquiry_id')->orderBy('sequence_no');
    }

    public function charges(): HasMany
    {
        return $this->hasMany(VendorPurchaseInquiryCharge::class, 'vendor_purchase_inquiry_id')->orderBy('sequence_no');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(VendorPurchaseInquiryAttachment::class, 'vendor_purchase_inquiry_id')->orderBy('sequence_no');
    }
}
