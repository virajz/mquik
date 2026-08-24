<?php

namespace App\Modules\JobCard\Models;

use App\Concerns\Auditable;
use App\Concerns\Searchable;
use App\Modules\Appointment\Models\Appointment;
use App\Modules\Appointment\Support\AppointmentStatus;
use App\Modules\CustomerApprovalTypeMaster\Models\CustomerApprovalTypeMaster;
use App\Modules\CustomerMaster\Models\CustomerMaster;
use App\Modules\CustomerVehicleMaster\Models\CustomerVehicleMaster;
use App\Modules\EmployeeMaster\Models\EmployeeMaster;
use App\Modules\GateInOut\Models\GateInOut;
use App\Modules\InsuranceCompanyMaster\Models\InsuranceCompanyMaster;
use App\Modules\JobCard\Database\Factories\JobCardFactory;
use App\Modules\JobCardCancelReasonMaster\Models\JobCardCancelReasonMaster;
use App\Modules\JobCardPendingReasonMaster\Models\JobCardPendingReasonMaster;
use App\Modules\JobDescriptionMaster\Models\JobDescriptionMaster;
use App\Modules\JobHistory\Models\JobCardHistoryEvent;
use App\Modules\JobHistory\Support\JobCardHistoryRecorder;
use App\Modules\JobStageMaster\Models\JobStageMaster;
use App\Modules\PickupDrop\Models\PickupDrop;
use App\Modules\RequestedRepairMaster\Models\RequestedRepairMaster;
use App\Modules\ServicePackageMaster\Models\ServicePackageMaster;
use App\Modules\ServiceTypeMaster\Models\ServiceTypeMaster;
use App\Modules\VendorMaster\Models\VendorMaster;
use App\Modules\WorkshopDepartmentMaster\Models\WorkshopDepartmentMaster;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class JobCard extends Model
{
    use Auditable;
    use HasFactory;
    use Searchable;

    public const STATUS_OPEN = 'open';

    public const STATUS_IN_PROGRESS = 'in_progress';

    public const STATUS_AWAITING_PARTS = 'awaiting_parts';

    public const STATUS_AWAITING_APPROVAL = 'awaiting_approval';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_CLOSED = 'closed';

    public const STATUS_CANCELLED = 'cancelled';

    protected $table = 'job_cards';

    protected $guarded = [];

    protected $casts = [
        'opened_at' => 'datetime',
        'technician_assigned_at' => 'datetime',
        'promised_at' => 'datetime',
        'expected_completion_at' => 'datetime',
        'closed_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'terms_accepted_at' => 'datetime',
        'terms_accepted' => 'boolean',
    ];

    /**
     * Includes the work itself (complaints and requested repairs), so an advisor
     * can search "oil change" and get every card where it was actually done —
     * the basis for spotting what a vehicle is due for.
     */
    protected static array $searchableFields = [
        'job_card_no', 'suggested_services', 'notes',
        'customer.first_name', 'customer.phone', 'customerVehicle.registration_no',
        'complaints.description', 'requestedRepairs.name',
    ];

    protected static function newFactory(): JobCardFactory
    {
        return JobCardFactory::new();
    }

    protected static function booted(): void
    {
        // Raising a job card is what finishes a booking — the reason the
        // appointment existed has now been fulfilled.
        static::saved(function (self $jobCard) {
            if ($jobCard->appointment_id) {
                AppointmentStatus::refresh($jobCard->appointment);
            }
        });

        // Stamp (or clear) the assignment time whenever the technician changes.
        static::saving(function (self $row) {
            if ($row->isDirty('assigned_technician_id')) {
                $row->technician_assigned_at = $row->assigned_technician_id ? now() : null;
            }
        });

        static::created(function (self $row) {
            if ($row->job_card_no === null) {
                $row->forceFill([
                    'job_card_no' => 'JC-'.str_pad((string) $row->id, 5, '0', STR_PAD_LEFT),
                ])->saveQuietly();
            }

            JobCardHistoryRecorder::record(
                $row->id,
                JobCardHistoryEvent::TYPE_CREATED,
                'Job Card created with status '.($row->status ?? self::STATUS_OPEN),
                ['status' => $row->status, 'advisor_id' => $row->assigned_advisor_id],
            );
        });

        static::updated(function (self $row) {
            $changed = $row->getChanges();

            if (isset($changed['status']) && $row->getOriginal('status') !== $row->status) {
                $eventType = $row->status === self::STATUS_CANCELLED
                    ? JobCardHistoryEvent::TYPE_CANCELLED
                    : JobCardHistoryEvent::TYPE_STATUS_CHANGED;
                JobCardHistoryRecorder::record(
                    $row->id,
                    $eventType,
                    'Status: '.$row->getOriginal('status').' → '.$row->status,
                    ['from' => $row->getOriginal('status'), 'to' => $row->status],
                );
            }

            if (isset($changed['assigned_advisor_id']) && $row->getOriginal('assigned_advisor_id') !== $row->assigned_advisor_id) {
                JobCardHistoryRecorder::record(
                    $row->id,
                    JobCardHistoryEvent::TYPE_ADVISOR_CHANGED,
                    'Advisor reassigned',
                    ['from' => $row->getOriginal('assigned_advisor_id'), 'to' => $row->assigned_advisor_id],
                );
            }

            if (array_key_exists('assigned_technician_id', $changed) && $row->getOriginal('assigned_technician_id') !== $row->assigned_technician_id) {
                JobCardHistoryRecorder::record(
                    $row->id,
                    JobCardHistoryEvent::TYPE_TECHNICIAN_CHANGED,
                    'Technician reassigned',
                    ['from' => $row->getOriginal('assigned_technician_id'), 'to' => $row->assigned_technician_id],
                );
            }

            if (array_key_exists('current_stage_id', $changed) && $row->getOriginal('current_stage_id') !== $row->current_stage_id) {
                $stageName = $row->current_stage_id
                    ? JobStageMaster::whereKey($row->current_stage_id)->value('name')
                    : null;
                JobCardHistoryRecorder::record(
                    $row->id,
                    JobCardHistoryEvent::TYPE_STAGE_CHANGED,
                    'Stage: '.($stageName ?? '—'),
                    ['from' => $row->getOriginal('current_stage_id'), 'to' => $row->current_stage_id],
                );
            }

            if (array_key_exists('pending_reason_id', $changed) && $row->getOriginal('pending_reason_id') !== $row->pending_reason_id) {
                $reasonName = $row->pending_reason_id
                    ? JobCardPendingReasonMaster::whereKey($row->pending_reason_id)->value('name')
                    : null;
                JobCardHistoryRecorder::record(
                    $row->id,
                    JobCardHistoryEvent::TYPE_PENDING_REASON_CHANGED,
                    $reasonName ? 'Pending reason: '.$reasonName : 'Pending reason cleared',
                    ['from' => $row->getOriginal('pending_reason_id'), 'to' => $row->pending_reason_id],
                );
            }
        });
    }

    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class, 'appointment_id');
    }

    public function gateEvent(): BelongsTo
    {
        return $this->belongsTo(GateInOut::class, 'gate_event_id');
    }

    public function repeatOf(): BelongsTo
    {
        return $this->belongsTo(self::class, 'repeat_of_job_card_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(CustomerMaster::class, 'customer_id');
    }

    public function customerVehicle(): BelongsTo
    {
        return $this->belongsTo(CustomerVehicleMaster::class, 'customer_vehicle_id');
    }

    public function workshopDepartment(): BelongsTo
    {
        return $this->belongsTo(WorkshopDepartmentMaster::class, 'workshop_department_id');
    }

    public function serviceType(): BelongsTo
    {
        return $this->belongsTo(ServiceTypeMaster::class, 'service_type_id');
    }

    public function servicePackage(): BelongsTo
    {
        return $this->belongsTo(ServicePackageMaster::class, 'service_package_id');
    }

    public function jobDescription(): BelongsTo
    {
        return $this->belongsTo(JobDescriptionMaster::class, 'job_description_id');
    }

    public function insuranceCompany(): BelongsTo
    {
        return $this->belongsTo(InsuranceCompanyMaster::class, 'insurance_company_id');
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(VendorMaster::class, 'vendor_id');
    }

    public function customerApprovalType(): BelongsTo
    {
        return $this->belongsTo(CustomerApprovalTypeMaster::class, 'customer_approval_type_id');
    }

    public function advisor(): BelongsTo
    {
        return $this->belongsTo(EmployeeMaster::class, 'assigned_advisor_id');
    }

    public function technician(): BelongsTo
    {
        return $this->belongsTo(EmployeeMaster::class, 'assigned_technician_id');
    }

    public function cancelReason(): BelongsTo
    {
        return $this->belongsTo(JobCardCancelReasonMaster::class, 'cancel_reason_id');
    }

    public function currentStage(): BelongsTo
    {
        return $this->belongsTo(JobStageMaster::class, 'current_stage_id');
    }

    public function pendingReason(): BelongsTo
    {
        return $this->belongsTo(JobCardPendingReasonMaster::class, 'pending_reason_id');
    }

    public function complaints(): HasMany
    {
        return $this->hasMany(JobCardComplaint::class, 'job_card_id')->orderBy('sequence_no');
    }

    public function pickupDrops(): HasMany
    {
        return $this->hasMany(PickupDrop::class, 'job_card_id');
    }

    public function inventoryItems(): HasMany
    {
        return $this->hasMany(JobCardInventoryItem::class, 'job_card_id');
    }

    public function photos(): HasMany
    {
        return $this->hasMany(JobCardPhoto::class, 'job_card_id')->orderBy('sequence_no');
    }

    public function requestedRepairs(): BelongsToMany
    {
        return $this->belongsToMany(
            RequestedRepairMaster::class,
            'job_card_requested_repair',
            'job_card_id',
            'requested_repair_id',
        )->withTimestamps();
    }

    /**
     * @return array<string, string>
     */
    public static function statuses(): array
    {
        return [
            self::STATUS_OPEN => 'Open',
            self::STATUS_IN_PROGRESS => 'In Progress',
            self::STATUS_AWAITING_PARTS => 'Awaiting Parts',
            self::STATUS_AWAITING_APPROVAL => 'Awaiting Approval',
            self::STATUS_COMPLETED => 'Completed',
            self::STATUS_CLOSED => 'Closed',
            self::STATUS_CANCELLED => 'Cancelled',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function fuelLevels(): array
    {
        return [
            'empty' => 'Empty',
            'quarter' => '1/4',
            'half' => '1/2',
            'three_quarter' => '3/4',
            'full' => 'Full',
        ];
    }

    /** How the vehicle arrived at the workshop. @return array<string, string> */
    public static function broughtByOptions(): array
    {
        return [
            'owner' => 'Owner',
            'driver' => 'Driver',
            'pickup' => 'Pickup',
            'towing' => 'Towing',
        ];
    }
}
