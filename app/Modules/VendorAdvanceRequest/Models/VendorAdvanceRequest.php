<?php

namespace App\Modules\VendorAdvanceRequest\Models;

use App\Concerns\Auditable;
use App\Concerns\Searchable;
use App\Modules\BankMaster\Models\BankMaster;
use App\Modules\CustomerMaster\Models\CustomerMaster;
use App\Modules\CustomerVehicleMaster\Models\CustomerVehicleMaster;
use App\Modules\EmployeeMaster\Models\EmployeeMaster;
use App\Modules\FollowUpModeMaster\Models\FollowUpModeMaster;
use App\Modules\JobCard\Models\JobCard;
use App\Modules\PriorityMaster\Models\PriorityMaster;
use App\Modules\VendorAdvanceRequest\Database\Factories\VendorAdvanceRequestFactory;
use App\Modules\VendorMaster\Models\VendorMaster;
use App\Modules\VendorPurchaseInquiry\Models\VendorPurchaseInquiry;
use App\Modules\VpoApproval\Models\VpoApproval;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VendorAdvanceRequest extends Model
{
    use Auditable;
    use HasFactory;
    use Searchable;

    public const STATUS_REQUESTED = 'requested';

    public const STATUS_IN_PROGRESS = 'in_progress';

    public const STATUS_PARTIALLY_PAID = 'partially_paid';

    public const STATUS_FULLY_PAID = 'fully_paid';

    public const STATUS_FAILED = 'failed';

    public const STATUS_ON_HOLD = 'on_hold';

    public const STATUS_REJECTED = 'rejected';

    public const STATUS_CANCELLED = 'cancelled';

    protected $table = 'vendor_advance_requests';

    protected $guarded = [];

    protected $casts = ['amount' => 'decimal:2', 'reminder_custom_days' => 'integer'];

    protected static array $searchableFields = ['request_no', 'notes', 'jobCard.job_card_no'];

    protected static function newFactory(): VendorAdvanceRequestFactory
    {
        return VendorAdvanceRequestFactory::new();
    }

    protected static function booted(): void
    {
        static::created(function (self $row) {
            if ($row->request_no === null) {
                $row->forceFill(['request_no' => 'VAR-'.str_pad((string) $row->id, 5, '0', STR_PAD_LEFT)])->saveQuietly();
            }
        });
    }

    /** @return array<string, string> */
    public static function statuses(): array
    {
        return [
            self::STATUS_REQUESTED => 'Requested',
            self::STATUS_IN_PROGRESS => 'In Progress',
            self::STATUS_PARTIALLY_PAID => 'Partially Paid',
            self::STATUS_FULLY_PAID => 'Fully Paid',
            self::STATUS_FAILED => 'Failed',
            self::STATUS_ON_HOLD => 'On Hold',
            self::STATUS_REJECTED => 'Rejected',
            self::STATUS_CANCELLED => 'Cancelled',
        ];
    }

    /** @return array<string, string> */
    public static function partsCategories(): array
    {
        return ['genuine' => 'Genuine', 'aftermarket' => 'After Market'];
    }

    /** @return array<string, string> */
    public static function advanceReasons(): array
    {
        return [
            'vendor_requires_advance' => 'Vendor Requires Advance',
            'imported_material' => 'Imported Material',
            'custom_manufacturing' => 'Custom Manufacturing',
            'emergency' => 'Emergency Requirement',
            'first_time_vendor' => 'First-Time Vendor',
            'odd_item' => 'Odd Item Purchase Order',
        ];
    }

    /** @return array<string, string> */
    public static function paymentModes(): array
    {
        return [
            'cash' => 'Cash', 'bank_transfer' => 'Bank Transfer', 'neft' => 'NEFT',
            'rtgs' => 'RTGS', 'imps' => 'IMPS', 'upi' => 'UPI', 'cheque' => 'Cheque',
        ];
    }

    /** @return array<string, string> */
    public static function vendorCategories(): array
    {
        return ['preferred' => 'Preferred Vendor', 'approved' => 'Approved Vendor', 'backup' => 'Backup Vendor'];
    }

    /** @return array<string, string> */
    public static function holdReasons(): array
    {
        return [
            'insufficient_documents' => 'Insufficient Documents',
            'budget_exceeded' => 'Budget Exceeded',
            'vendor_verification_pending' => 'Vendor Verification Pending',
        ];
    }

    /** @return array<string, string> */
    public static function rejectionReasons(): array
    {
        return [
            'high_price' => 'High Price',
            'wrong_vendor' => 'Wrong Vendor',
            'budget_issue' => 'Budget Issue',
            'duplicate' => 'Duplicate Advance Request',
            'qty_exceeded' => 'Quantity Exceeded',
            'vendor_not_supportive' => 'Vendor Not Supportive',
        ];
    }

    /** @return array<string, string> */
    public static function reminderFrequencies(): array
    {
        return ['daily' => 'Daily', 'every_2_days' => 'Every 2 Days', 'custom' => 'Custom'];
    }

    /** @return list<string> */
    public static function standardDocuments(): array
    {
        return ['QUOTE / PROFORMA INVOICE', 'APPROVAL NOTE', 'VENDOR BANK DETAILS'];
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

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(VendorMaster::class, 'vendor_id');
    }

    public function inquiry(): BelongsTo
    {
        return $this->belongsTo(VendorPurchaseInquiry::class, 'vendor_purchase_inquiry_id');
    }

    public function poApproval(): BelongsTo
    {
        return $this->belongsTo(VpoApproval::class, 'vpo_approval_id');
    }

    public function priority(): BelongsTo
    {
        return $this->belongsTo(PriorityMaster::class, 'priority_id');
    }

    public function bank(): BelongsTo
    {
        return $this->belongsTo(BankMaster::class, 'bank_id');
    }

    public function preparedBy(): BelongsTo
    {
        return $this->belongsTo(EmployeeMaster::class, 'prepared_by_id');
    }

    public function followUpMode(): BelongsTo
    {
        return $this->belongsTo(FollowUpModeMaster::class, 'follow_up_mode_id');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(VendorAdvanceRequestDocument::class, 'vendor_advance_request_id')->orderBy('sequence_no');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(VendorAdvanceRequestAttachment::class, 'vendor_advance_request_id')->orderBy('sequence_no');
    }
}
