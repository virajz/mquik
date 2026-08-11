<?php

namespace App\Modules\DeliveryOrder\Models;

use App\Concerns\Auditable;
use App\Concerns\Searchable;
use App\Modules\CustomerMaster\Models\CustomerMaster;
use App\Modules\CustomerVehicleMaster\Models\CustomerVehicleMaster;
use App\Modules\DeliveryOrder\Database\Factories\DeliveryOrderFactory;
use App\Modules\EmployeeMaster\Models\EmployeeMaster;
use App\Modules\FollowUpModeMaster\Models\FollowUpModeMaster;
use App\Modules\InsuranceCompanyMaster\Models\InsuranceCompanyMaster;
use App\Modules\JobCard\Models\JobCard;
use App\Modules\ProformaApproval\Models\ProformaApproval;
use App\Modules\WorkshopDepartmentMaster\Models\WorkshopDepartmentMaster;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DeliveryOrder extends Model
{
    use Auditable;
    use HasFactory;
    use Searchable;

    public const STATUS_REQUESTED = 'requested';

    public const STATUS_UNDER_VERIFICATION = 'under_verification';

    public const STATUS_ON_HOLD = 'on_hold';

    public const STATUS_RECEIVED = 'do_received';

    public const STATUS_REQUESTED_TO_SETTLE = 'requested_to_settle';

    public const STATUS_MISMATCH_APPROVED = 'mismatch_approved';

    public const STATUS_CANCELLED = 'cancelled';

    protected $table = 'delivery_orders';

    protected $guarded = [];

    protected $casts = [
        'claim_date' => 'date',
        'proforma_amount' => 'decimal:2',
        'do_amount' => 'decimal:2',
        'reminder_custom_days' => 'integer',
        'requested_at' => 'datetime',
        'do_received_at' => 'datetime',
        'do_entry_at' => 'datetime',
        'approved_at' => 'datetime',
    ];

    protected static array $searchableFields = ['do_no', 'claim_number', 'policy_number', 'notes', 'jobCard.job_card_no'];

    protected static function newFactory(): DeliveryOrderFactory
    {
        return DeliveryOrderFactory::new();
    }

    protected static function booted(): void
    {
        static::created(function (self $row) {
            if ($row->do_no === null) {
                $row->forceFill(['do_no' => 'DO-'.str_pad((string) $row->id, 5, '0', STR_PAD_LEFT)])->saveQuietly();
            }
        });
    }

    /** True when the DO amount differs from the proforma amount (both present). */
    public function hasMismatch(): bool
    {
        return $this->do_amount !== null
            && $this->proforma_amount !== null
            && (float) $this->do_amount !== (float) $this->proforma_amount;
    }

    /** DO amount minus proforma amount (negative = insurer reduced it). */
    public function mismatchDelta(): ?float
    {
        if ($this->do_amount === null || $this->proforma_amount === null) {
            return null;
        }

        return (float) $this->do_amount - (float) $this->proforma_amount;
    }

    /** @return array<string, string> */
    public static function statuses(): array
    {
        return [
            self::STATUS_REQUESTED => 'Requested',
            self::STATUS_UNDER_VERIFICATION => 'Under Verification',
            self::STATUS_ON_HOLD => 'On Hold',
            self::STATUS_RECEIVED => 'DO Received',
            self::STATUS_REQUESTED_TO_SETTLE => 'Requested to Settle DO Amt',
            self::STATUS_MISMATCH_APPROVED => 'Mismatch Approved',
            self::STATUS_CANCELLED => 'Cancelled',
        ];
    }

    /** @return array<string, string> */
    public static function mismatchReasons(): array
    {
        return [
            'labour_reduction' => 'Labour Reduction',
            'parts_reduction' => 'Parts Reduction',
            'paint_reduction' => 'Paint Material Reduction',
            'depreciation' => 'Depreciation Applied',
            'non_approved_item' => 'Non-Approved Item',
            'policy_limitation' => 'Policy Limitation',
        ];
    }

    /** @return array<string, string> */
    public static function reminderFrequencies(): array
    {
        return ['daily' => 'Daily', 'every_2_days' => 'Every 2 Days', 'custom' => 'Custom'];
    }

    public function jobCard(): BelongsTo
    {
        return $this->belongsTo(JobCard::class, 'job_card_id');
    }

    public function proformaApproval(): BelongsTo
    {
        return $this->belongsTo(ProformaApproval::class, 'proforma_approval_id');
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(WorkshopDepartmentMaster::class, 'workshop_department_id');
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(EmployeeMaster::class, 'employee_id');
    }

    public function insuranceCompany(): BelongsTo
    {
        return $this->belongsTo(InsuranceCompanyMaster::class, 'insurance_company_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(CustomerMaster::class, 'customer_id');
    }

    public function customerVehicle(): BelongsTo
    {
        return $this->belongsTo(CustomerVehicleMaster::class, 'customer_vehicle_id');
    }

    public function followUpMode(): BelongsTo
    {
        return $this->belongsTo(FollowUpModeMaster::class, 'follow_up_mode_id');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(DeliveryOrderAttachment::class, 'delivery_order_id')->orderBy('sequence_no');
    }
}
