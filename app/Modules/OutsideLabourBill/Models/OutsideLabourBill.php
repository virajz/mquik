<?php

namespace App\Modules\OutsideLabourBill\Models;

use App\Concerns\Auditable;
use App\Concerns\Searchable;
use App\Modules\EmployeeMaster\Models\EmployeeMaster;
use App\Modules\FollowUpModeMaster\Models\FollowUpModeMaster;
use App\Modules\OutsideLabourBill\Database\Factories\OutsideLabourBillFactory;
use App\Modules\OutsideLabourOrder\Models\OutsideLabourOrder;
use App\Modules\PriorityMaster\Models\PriorityMaster;
use App\Modules\ServiceSpecialistMaster\Models\ServiceSpecialistMaster;
use App\Modules\VendorMaster\Models\VendorMaster;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OutsideLabourBill extends Model
{
    use Auditable;
    use HasFactory;
    use Searchable;

    public const STATUS_REQUESTED = 'requested';

    public const STATUS_UNDER_VERIFICATION = 'under_verification';

    public const STATUS_ON_HOLD = 'on_hold';

    public const STATUS_PARTIALLY_VERIFIED = 'partially_verified';

    public const STATUS_FULLY_VERIFIED = 'fully_verified';

    public const STATUS_REJECTED = 'rejected';

    public const STATUS_CANCELLED = 'cancelled';

    protected $table = 'outside_labour_bills';

    protected $guarded = [];

    protected $casts = [
        'bill_date' => 'date',
        'bill_amount' => 'decimal:2',
        'reminder_custom_days' => 'integer',
    ];

    protected static array $searchableFields = ['bill_no', 'vendor_bill_no', 'notes'];

    protected static function newFactory(): OutsideLabourBillFactory
    {
        return OutsideLabourBillFactory::new();
    }

    protected static function booted(): void
    {
        static::created(function (self $row) {
            if ($row->bill_no === null) {
                $row->forceFill(['bill_no' => 'OLB-'.str_pad((string) $row->id, 5, '0', STR_PAD_LEFT)])->saveQuietly();
            }
        });
    }

    /** @return array<string, string> */
    public static function statuses(): array
    {
        return [
            self::STATUS_REQUESTED => 'Requested',
            self::STATUS_UNDER_VERIFICATION => 'Under Verification',
            self::STATUS_ON_HOLD => 'On Hold',
            self::STATUS_PARTIALLY_VERIFIED => 'Partially Verified',
            self::STATUS_FULLY_VERIFIED => 'Fully Verified',
            self::STATUS_REJECTED => 'Rejected',
            self::STATUS_CANCELLED => 'Cancelled',
        ];
    }

    /** @return array<string, string> */
    public static function billDocumentTypes(): array
    {
        return [
            'tax_invoice' => 'Tax Invoice',
            'bill_of_supply' => 'Bill of Supply',
            'e_invoice' => 'E-Invoice',
            'bill_book_memo' => 'Bill Book Memo',
        ];
    }

    /** @return array<string, string> */
    public static function workCompletionTypes(): array
    {
        return [
            'pending' => 'Pending',
            'partially_completed' => 'Partially Completed',
            'fully_completed' => 'Fully Completed',
            'deferred' => 'Deferred',
        ];
    }

    /** @return array<string, string> */
    public static function holdReasons(): array
    {
        return [
            'rate_difference' => 'Rate Difference',
            'quantity_difference' => 'Quantity Difference',
            'additional_work' => 'Additional Work',
            'incorrect_billing' => 'Incorrect Billing',
            'duplicate_billing' => 'Duplicate Billing',
            'damaged_copy' => 'Damaged Copy',
            'missing_pages' => 'Missing Pages',
        ];
    }

    /** @return array<string, string> */
    public static function rejectionReasons(): array
    {
        return [
            'rate_mismatch' => 'Rate Mismatch',
            'quantity_mismatch' => 'Quantity Mismatch',
            'job_description_mismatch' => 'Job Description Mismatch',
            'additional_work' => 'Additional Work',
            'vendor_name_mismatch' => 'Vendor Name Mismatch',
            'duplicate_billing' => 'Duplicate Billing',
            'damaged_copy' => 'Damaged Copy',
            'missing_pages' => 'Missing Pages',
        ];
    }

    /** @return array<string, string> */
    public static function reminderFrequencies(): array
    {
        return ['daily' => 'Daily', 'every_2_days' => 'Every 2 Days', 'custom' => 'Custom'];
    }

    /** @return array<string, string> */
    public static function vendorRatingTypes(): array
    {
        return [
            'quality' => 'Quality Service',
            'price' => 'Reasonable Price',
            'delivery' => 'Ontime Delivery',
            'support' => 'Handhold Support',
        ];
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(VendorMaster::class, 'vendor_id');
    }

    public function workCategory(): BelongsTo
    {
        return $this->belongsTo(ServiceSpecialistMaster::class, 'service_specialist_id');
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(OutsideLabourOrder::class, 'outside_labour_order_id');
    }

    public function priority(): BelongsTo
    {
        return $this->belongsTo(PriorityMaster::class, 'priority_id');
    }

    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(EmployeeMaster::class, 'requested_by_id');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(EmployeeMaster::class, 'approved_by_id');
    }

    public function followUpMode(): BelongsTo
    {
        return $this->belongsTo(FollowUpModeMaster::class, 'follow_up_mode_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(OutsideLabourBillItem::class, 'outside_labour_bill_id')->orderBy('sequence_no');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(OutsideLabourBillAttachment::class, 'outside_labour_bill_id')->orderBy('sequence_no');
    }
}
