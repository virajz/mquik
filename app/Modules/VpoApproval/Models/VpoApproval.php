<?php

namespace App\Modules\VpoApproval\Models;

use App\Concerns\Auditable;
use App\Concerns\Searchable;
use App\Modules\CustomerApprovalTypeMaster\Models\CustomerApprovalTypeMaster;
use App\Modules\CustomerMaster\Models\CustomerMaster;
use App\Modules\CustomerVehicleMaster\Models\CustomerVehicleMaster;
use App\Modules\EmployeeMaster\Models\EmployeeMaster;
use App\Modules\JobCard\Models\JobCard;
use App\Modules\PartTypeMaster\Models\PartTypeMaster;
use App\Modules\PriorityMaster\Models\PriorityMaster;
use App\Modules\VendorMaster\Models\VendorMaster;
use App\Modules\VendorPurchaseInquiry\Models\VendorPurchaseInquiry;
use App\Modules\VpoApproval\Database\Factories\VpoApprovalFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VpoApproval extends Model
{
    use Auditable;
    use HasFactory;
    use Searchable;

    public const STATUS_SENT = 'sent';

    public const STATUS_PARTIALLY_APPROVED = 'partially_approved';

    public const STATUS_FULLY_APPROVED = 'fully_approved';

    public const STATUS_UNDER_REVIEW = 'under_review';

    public const STATUS_REJECTED = 'rejected';

    public const STATUS_REAPPROVED = 'reapproved';

    protected $table = 'vpo_approvals';

    protected $guarded = [];

    protected $casts = ['approved_at' => 'datetime'];

    protected static array $searchableFields = ['approval_no', 'notes', 'jobCard.job_card_no'];

    protected static function newFactory(): VpoApprovalFactory
    {
        return VpoApprovalFactory::new();
    }

    protected static function booted(): void
    {
        static::created(function (self $row) {
            if ($row->approval_no === null) {
                $row->forceFill(['approval_no' => 'VPA-'.str_pad((string) $row->id, 5, '0', STR_PAD_LEFT)])->saveQuietly();
            }
        });
    }

    /** @return array<string, string> */
    public static function statuses(): array
    {
        return [
            self::STATUS_SENT => 'Sent for Approval',
            self::STATUS_PARTIALLY_APPROVED => 'Partially Approved',
            self::STATUS_FULLY_APPROVED => 'Fully Approved',
            self::STATUS_UNDER_REVIEW => 'Under Review',
            self::STATUS_REJECTED => 'Rejected',
            self::STATUS_REAPPROVED => 'Re-approved (after revision)',
        ];
    }

    /** @return array<string, string> */
    public static function poApprovalTypes(): array
    {
        return [
            'stock_bulk' => 'Stock Bulk Purchase',
            'odd_item' => 'Odd Item Purchase',
            'high_value' => 'High Value Purchase',
            'rate_contract' => 'Rate Contract Purchase',
            'job_card' => 'Job Card Purchase',
            'emergency' => 'Emergency Purchase',
        ];
    }

    /** @return array<string, string> */
    public static function approvalLevels(): array
    {
        return [
            'l1_store' => 'L1 – Store Manager',
            'l2_accounts' => 'L2 – Accounts Manager',
            'l3_owner' => 'L3 – Owner / Admin',
        ];
    }

    /** @return array<string, string> */
    public static function paymentTerms(): array
    {
        return [
            'advance' => 'Advance',
            'cod' => 'Cash on Delivery',
            'credit_7' => '7 Days',
            'credit_15' => '15 Days',
            'credit_30' => '30 Days',
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
    public static function rejectionReasons(): array
    {
        return [
            'high_price' => 'High Price',
            'wrong_vendor' => 'Wrong Vendor',
            'budget_exceeded' => 'Budget Exceeded',
            'duplicate_po' => 'Duplicate PO',
            'qty_exceeded' => 'Quantity Exceeded',
        ];
    }

    public function jobCard(): BelongsTo
    {
        return $this->belongsTo(JobCard::class, 'job_card_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(CustomerMaster::class, 'customer_id');
    }

    public function customerVehicle(): BelongsTo
    {
        return $this->belongsTo(CustomerVehicleMaster::class, 'customer_vehicle_id');
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(EmployeeMaster::class, 'employee_id');
    }

    public function inquiry(): BelongsTo
    {
        return $this->belongsTo(VendorPurchaseInquiry::class, 'vendor_purchase_inquiry_id');
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(VendorMaster::class, 'vendor_id');
    }

    public function partType(): BelongsTo
    {
        return $this->belongsTo(PartTypeMaster::class, 'part_type_id');
    }

    public function priority(): BelongsTo
    {
        return $this->belongsTo(PriorityMaster::class, 'priority_id');
    }

    public function approvalMode(): BelongsTo
    {
        return $this->belongsTo(CustomerApprovalTypeMaster::class, 'approval_mode_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(VpoApprovalItem::class, 'vpo_approval_id')->orderBy('sequence_no');
    }

    public function charges(): HasMany
    {
        return $this->hasMany(VpoApprovalCharge::class, 'vpo_approval_id')->orderBy('sequence_no');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(VpoApprovalAttachment::class, 'vpo_approval_id')->orderBy('sequence_no');
    }
}
