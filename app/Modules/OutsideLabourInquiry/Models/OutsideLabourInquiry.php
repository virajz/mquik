<?php

namespace App\Modules\OutsideLabourInquiry\Models;

use App\Concerns\Auditable;
use App\Concerns\Searchable;
use App\Modules\CustomerMaster\Models\CustomerMaster;
use App\Modules\CustomerVehicleMaster\Models\CustomerVehicleMaster;
use App\Modules\EmployeeMaster\Models\EmployeeMaster;
use App\Modules\EstimateRevisionReasonMaster\Models\EstimateRevisionReasonMaster;
use App\Modules\FollowUpModeMaster\Models\FollowUpModeMaster;
use App\Modules\HsnMaster\Models\HsnMaster;
use App\Modules\JobCard\Models\JobCard;
use App\Modules\OutsideLabourInquiry\Database\Factories\OutsideLabourInquiryFactory;
use App\Modules\OutsideLabourRejectionReasonMaster\Models\OutsideLabourRejectionReasonMaster;
use App\Modules\PriorityMaster\Models\PriorityMaster;
use App\Modules\ServiceSpecialistMaster\Models\ServiceSpecialistMaster;
use App\Modules\TaxMaster\Models\TaxMaster;
use App\Modules\VendorMaster\Models\VendorMaster;
use App\Modules\WorkshopDepartmentMaster\Models\WorkshopDepartmentMaster;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OutsideLabourInquiry extends Model
{
    use Auditable;
    use HasFactory;
    use Searchable;

    public const STATUS_RESPONSE_PENDING = 'response_pending';

    public const STATUS_PARTIALLY_RESPONDED = 'partially_responded';

    public const STATUS_FULLY_RESPONDED = 'fully_responded';

    public const STATUS_WORK_ORDER_ISSUED = 'work_order_issued';

    public const STATUS_REJECTED = 'rejected';

    public const STATUS_CANCELLED = 'cancelled';

    protected $table = 'outside_labour_inquiries';

    protected $guarded = [];

    protected $casts = [
        'promised_from' => 'datetime',
        'promised_to' => 'datetime',
        'tat_custom_days' => 'integer',
        'reminder_custom_days' => 'integer',
    ];

    protected static array $searchableFields = ['inquiry_no', 'notes'];

    protected static function newFactory(): OutsideLabourInquiryFactory
    {
        return OutsideLabourInquiryFactory::new();
    }

    protected static function booted(): void
    {
        static::created(function (self $row) {
            if ($row->inquiry_no === null) {
                $row->forceFill([
                    'inquiry_no' => 'OLI-'.str_pad((string) $row->id, 5, '0', STR_PAD_LEFT),
                ])->saveQuietly();
            }
        });
    }

    /** @return array<string, string> */
    public static function statuses(): array
    {
        return [
            self::STATUS_RESPONSE_PENDING => 'Response Pending',
            self::STATUS_PARTIALLY_RESPONDED => 'Partially Responded',
            self::STATUS_FULLY_RESPONDED => 'Fully Responded',
            self::STATUS_WORK_ORDER_ISSUED => 'Work Order Issued',
            self::STATUS_REJECTED => 'Rejected by Contractor',
            self::STATUS_CANCELLED => 'Cancelled',
        ];
    }

    /** @return array<string, string> */
    public static function communicationModes(): array
    {
        return [
            'whatsapp' => 'WhatsApp',
            'email' => 'Email',
            'phone' => 'Phone Call',
        ];
    }

    /** @return array<string, string> */
    public static function tatOptions(): array
    {
        return [
            'one_day' => '1 Day',
            'two_days' => '2 Days',
            'three_days' => '3 Days',
            'custom' => 'Custom',
        ];
    }

    /** @return array<string, string> */
    public static function reminderFrequencies(): array
    {
        return [
            'daily' => 'Daily',
            'every_2_days' => 'Every 2 Days',
            'custom' => 'Custom',
        ];
    }

    /** @return array<string, string> */
    public static function notificationStages(): array
    {
        return [
            'inquiry_sent' => 'Inquiry Sent',
            'response_received' => 'Response Received',
            'approved' => 'Approved',
        ];
    }

    /**
     * Reminder interval in days, resolved from the frequency + custom override.
     * Config only — nothing is dispatched yet.
     */
    public function reminderIntervalDays(): ?int
    {
        return match ($this->reminder_frequency) {
            'daily' => 1,
            'every_2_days' => 2,
            'custom' => $this->reminder_custom_days,
            default => null,
        };
    }

    public function inquiryType(): BelongsTo
    {
        return $this->belongsTo(ServiceSpecialistMaster::class, 'inquiry_type_id');
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(WorkshopDepartmentMaster::class, 'workshop_department_id');
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(EmployeeMaster::class, 'employee_id');
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

    public function hsn(): BelongsTo
    {
        return $this->belongsTo(HsnMaster::class, 'hsn_id');
    }

    public function tax(): BelongsTo
    {
        return $this->belongsTo(TaxMaster::class, 'tax_id');
    }

    public function priority(): BelongsTo
    {
        return $this->belongsTo(PriorityMaster::class, 'priority_id');
    }

    public function revisionReason(): BelongsTo
    {
        return $this->belongsTo(EstimateRevisionReasonMaster::class, 'revision_reason_id');
    }

    public function rejectionReason(): BelongsTo
    {
        return $this->belongsTo(OutsideLabourRejectionReasonMaster::class, 'rejection_reason_id');
    }

    public function followUpMode(): BelongsTo
    {
        return $this->belongsTo(FollowUpModeMaster::class, 'follow_up_mode_id');
    }

    public function scopes(): HasMany
    {
        return $this->hasMany(OutsideLabourInquiryScope::class, 'outside_labour_inquiry_id')->orderBy('sequence_no');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(OutsideLabourInquiryAttachment::class, 'outside_labour_inquiry_id')->orderBy('sequence_no');
    }
}
