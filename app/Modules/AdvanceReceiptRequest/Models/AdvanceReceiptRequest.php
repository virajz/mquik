<?php

namespace App\Modules\AdvanceReceiptRequest\Models;

use App\Concerns\Auditable;
use App\Concerns\Searchable;
use App\Modules\AdvanceReceiptRequest\Database\Factories\AdvanceReceiptRequestFactory;
use App\Modules\CustomerMaster\Models\CustomerMaster;
use App\Modules\CustomerVehicleMaster\Models\CustomerVehicleMaster;
use App\Modules\EmployeeMaster\Models\EmployeeMaster;
use App\Modules\FollowUpModeMaster\Models\FollowUpModeMaster;
use App\Modules\JobCard\Models\JobCard;
use App\Modules\SalesEstimate\Models\SalesEstimate;
use App\Modules\ServiceTypeMaster\Models\ServiceTypeMaster;
use App\Modules\WorkshopDepartmentMaster\Models\WorkshopDepartmentMaster;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AdvanceReceiptRequest extends Model
{
    use Auditable;
    use HasFactory;
    use Searchable;

    public const STATUS_REQUESTED = 'requested';

    public const STATUS_PARTIALLY_PAID = 'partially_paid';

    public const STATUS_FULLY_PAID = 'fully_paid';

    public const STATUS_CANCELLED = 'cancelled';

    public const STATUS_FAILED = 'failed';

    public const STATUS_REJECTED = 'rejected';

    public const STATUS_REFUNDED = 'refunded';

    protected $table = 'advance_receipt_requests';

    protected $guarded = [];

    protected $casts = [
        'percent' => 'decimal:2',
        'amount' => 'decimal:2',
    ];

    protected static array $searchableFields = ['request_no', 'notes', 'jobCard.job_card_no'];

    protected static function newFactory(): AdvanceReceiptRequestFactory
    {
        return AdvanceReceiptRequestFactory::new();
    }

    protected static function booted(): void
    {
        static::created(function (self $row) {
            if ($row->request_no === null) {
                $row->forceFill([
                    'request_no' => 'ARR-'.str_pad((string) $row->id, 5, '0', STR_PAD_LEFT),
                ])->saveQuietly();
            }
        });
    }

    /** @return array<string, string> */
    public static function paymentStatuses(): array
    {
        return [
            self::STATUS_REQUESTED => 'Requested',
            self::STATUS_PARTIALLY_PAID => 'Partially Paid',
            self::STATUS_FULLY_PAID => 'Fully Paid',
            self::STATUS_CANCELLED => 'Cancelled',
            self::STATUS_FAILED => 'Failed',
            self::STATUS_REJECTED => 'Rejected',
            self::STATUS_REFUNDED => 'Refunded',
        ];
    }

    /** @return array<string, string> */
    public static function advancePurposes(): array
    {
        return [
            'odd_item_po' => 'Odd Item Purchase Order',
            'urgent_po' => 'Urgent Purchase Order',
            'regular_parts' => 'Regular Parts Purchase',
            'outside_labour' => 'Outside Labour Booking',
        ];
    }

    /** @return array<string, string> */
    public static function amountTypes(): array
    {
        return [
            'percent_of_estimate' => 'Specific % of Estimate',
            'minimum' => 'Minimum Amount',
            'maximum' => 'Maximum Amount',
            'custom' => 'Custom Amount',
        ];
    }

    /** @return array<string, string> */
    public static function reminderTimes(): array
    {
        return [
            'daily_10am' => 'Daily at 10:00 AM',
            'afternoon_2pm' => 'Afternoon at 02:00 PM',
            'evening_4pm' => 'Evening at 04:00 PM',
            'custom' => 'Custom',
        ];
    }

    /** @return array<string, string> */
    public static function rejectionReasons(): array
    {
        return [
            'customer_not_interested' => 'Customer Not Interested',
            'budget_issue' => 'Budget Issue',
            'delay_estimate' => 'Delay in Service Estimate',
            'insurance_limitation' => 'Insurance Coverage Limitation',
        ];
    }

    public function jobCard(): BelongsTo
    {
        return $this->belongsTo(JobCard::class, 'job_card_id');
    }

    public function salesEstimate(): BelongsTo
    {
        return $this->belongsTo(SalesEstimate::class, 'sales_estimate_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(CustomerMaster::class, 'customer_id');
    }

    public function customerVehicle(): BelongsTo
    {
        return $this->belongsTo(CustomerVehicleMaster::class, 'customer_vehicle_id');
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(WorkshopDepartmentMaster::class, 'workshop_department_id');
    }

    public function serviceType(): BelongsTo
    {
        return $this->belongsTo(ServiceTypeMaster::class, 'service_type_id');
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(EmployeeMaster::class, 'employee_id');
    }

    public function followUpMode(): BelongsTo
    {
        return $this->belongsTo(FollowUpModeMaster::class, 'follow_up_mode_id');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(AdvanceReceiptRequestAttachment::class, 'advance_receipt_request_id')->orderBy('sequence_no');
    }
}
