<?php

namespace App\Modules\VisitorManagement\Models;

use App\Concerns\Auditable;
use App\Concerns\Searchable;
use App\Modules\CustomerMaster\Models\CustomerMaster;
use App\Modules\CustomerVehicleMaster\Models\CustomerVehicleMaster;
use App\Modules\EmployeeMaster\Models\EmployeeMaster;
use App\Modules\VisitorManagement\Database\Factories\VisitorVisitFactory;
use App\Modules\WorkshopDepartmentMaster\Models\WorkshopDepartmentMaster;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VisitorVisit extends Model
{
    use Auditable;
    use HasFactory;
    use Searchable;

    public const STATUS_ADVISOR_ASSIGNED = 'advisor_assigned';

    public const STATUS_CONSULTATION_STARTED = 'consultation_started';

    public const STATUS_CONSULTATION_COMPLETED = 'consultation_completed';

    public const STATUS_JOB_CARD_CREATED = 'job_card_created';

    public const STATUS_CANCELLED = 'cancelled';

    protected $table = 'visitor_visits';

    protected $guarded = [];

    protected $casts = [
        'arrival_at' => 'datetime',
        'advisor_assigned_at' => 'datetime',
        'consultation_started_at' => 'datetime',
        'consultation_ended_at' => 'datetime',
        'job_card_created_at' => 'datetime',
        'exit_at' => 'datetime',
    ];

    protected static array $searchableFields = ['token_no', 'announcement_message', 'notes'];

    protected static function newFactory(): VisitorVisitFactory
    {
        return VisitorVisitFactory::new();
    }

    protected static function booted(): void
    {
        static::created(function (self $row) {
            if ($row->token_no === null) {
                $row->forceFill(['token_no' => 'VMS-'.str_pad((string) $row->id, 5, '0', STR_PAD_LEFT)])->saveQuietly();
            }
        });
    }

    /** Statuses still waiting in the queue (not job-card / cancelled). @return list<string> */
    public static function openStatuses(): array
    {
        return [
            self::STATUS_ADVISOR_ASSIGNED,
            self::STATUS_CONSULTATION_STARTED,
            self::STATUS_CONSULTATION_COMPLETED,
        ];
    }

    /** @return array<string, string> */
    public static function statuses(): array
    {
        return [
            self::STATUS_ADVISOR_ASSIGNED => 'Advisor Assigned',
            self::STATUS_CONSULTATION_STARTED => 'Consultation Started',
            self::STATUS_CONSULTATION_COMPLETED => 'Consultation Completed',
            self::STATUS_JOB_CARD_CREATED => 'Job Card Created',
            self::STATUS_CANCELLED => 'Cancelled',
        ];
    }

    /** @return array<string, string> */
    public static function customerTypes(): array
    {
        return [
            'senior_citizen' => 'Senior Citizen',
            'lady_customer' => 'Lady Customer',
            'gents_customer' => 'Gents Customer',
        ];
    }

    /** @return array<string, string> */
    public static function visitPurposes(): array
    {
        return [
            'periodic_maintenance' => 'Periodic Maintenance',
            'general_repair' => 'General Repair',
            'accident_repair' => 'Accident Repair',
            'insurance_claim' => 'Insurance Claim',
            'vehicle_delivery' => 'Vehicle Delivery',
            'surveyor_inspection' => 'Surveyor Inspection',
            'consultation' => 'Consultation',
        ];
    }

    /** @return array<string, string> */
    public static function arrivalModes(): array
    {
        return [
            'walk_in' => 'Walk In',
            'appointment' => 'Appointment',
            'phone_call' => 'Phone Call',
            'crm_followup' => 'CRM Follow-up',
            'mobile_app' => 'Mobile App',
            'website' => 'Website',
        ];
    }

    /** @return array<string, string> */
    public static function advisorAssignmentMethods(): array
    {
        return [
            'auto' => 'Automatic',
            'manual' => 'Manual',
            'least_busy' => 'Least Busy',
            'preferred' => 'Preferred Advisor',
        ];
    }

    /** @return array<string, string> */
    public static function advisorAvailabilities(): array
    {
        return [
            'available' => 'Available',
            'busy' => 'Busy',
            'on_break' => 'On Break',
            'meeting' => 'In Meeting',
            'lunch' => 'At Lunch',
            'leave' => 'On Leave',
        ];
    }

    /** @return array<string, string> */
    public static function waitingTimeCategories(): array
    {
        return [
            'lt_10' => 'Less than 10 Minutes',
            '10_20' => '10 to 20 Minutes',
            '20_30' => '20 to 30 Minutes',
            '30_45' => '30 to 45 Minutes',
            '45_60' => '45 to 60 Minutes',
            'gt_60' => 'More than 60 Minutes',
        ];
    }

    /** @return array<string, string> */
    public static function waitingStatuses(): array
    {
        return self::statuses();
    }

    /** @return array<string, string> */
    public static function delayReasons(): array
    {
        return [
            'advisor_busy' => 'Advisor Busy',
            'workload_high' => 'High Workload',
            'customer_late' => 'Customer Late',
            'system_issue' => 'System Issue',
        ];
    }

    /** @return array<string, string> */
    public static function noShowReasons(): array
    {
        return [
            'customer_left' => 'Customer Left',
            'customer_cancelled' => 'Customer Cancelled',
        ];
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
        return $this->belongsTo(WorkshopDepartmentMaster::class, 'department_id');
    }

    public function assignedBy(): BelongsTo
    {
        return $this->belongsTo(EmployeeMaster::class, 'assigned_by_id');
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(EmployeeMaster::class, 'assigned_to_id');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(VisitorVisitAttachment::class, 'visitor_visit_id')->orderBy('sequence_no');
    }
}
