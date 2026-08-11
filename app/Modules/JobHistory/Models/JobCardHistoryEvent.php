<?php

namespace App\Modules\JobHistory\Models;

use App\Models\User;
use App\Modules\CustomerVehicleMaster\Models\CustomerVehicleMaster;
use App\Modules\JobCard\Models\JobCard;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JobCardHistoryEvent extends Model
{
    public const TYPE_CREATED = 'created';

    public const TYPE_STATUS_CHANGED = 'status_changed';

    public const TYPE_ADVISOR_CHANGED = 'advisor_changed';

    public const TYPE_TECHNICIAN_CHANGED = 'technician_changed';

    public const TYPE_STAGE_CHANGED = 'stage_changed';

    public const TYPE_PENDING_REASON_CHANGED = 'pending_reason_changed';

    public const TYPE_COMPLAINT_ADDED = 'complaint_added';

    public const TYPE_COMPLAINT_RESOLVED = 'complaint_resolved';

    public const TYPE_INSPECTION_STARTED = 'inspection_started';

    public const TYPE_INSPECTION_COMPLETED = 'inspection_completed';

    public const TYPE_WORK_ORDER_ASSIGNED = 'work_order_assigned';

    public const TYPE_WORK_ORDER_STARTED = 'work_order_started';

    public const TYPE_WORK_ORDER_HELD = 'work_order_held';

    public const TYPE_WORK_ORDER_COMPLETED = 'work_order_completed';

    public const TYPE_FINDING_RECORDED = 'finding_recorded';

    public const TYPE_CANCELLED = 'cancelled';

    // ---- Vehicle-level events (may occur before or after a job card exists) ----

    public const TYPE_APPOINTMENT_BOOKED = 'appointment_booked';

    public const TYPE_VEHICLE_ARRIVED = 'vehicle_arrived';

    public const TYPE_VEHICLE_DEPARTED = 'vehicle_departed';

    public const TYPE_PICKUP_SCHEDULED = 'pickup_scheduled';

    public const TYPE_PARTS_INQUIRY_RAISED = 'parts_inquiry_raised';

    public const TYPE_DOCUMENT_REQUESTED = 'document_requested';

    public const TYPE_DOCUMENT_RECEIVED = 'document_received';

    protected $table = 'job_card_history_events';

    protected $guarded = [];

    protected $casts = [
        'occurred_at' => 'datetime',
        'payload' => 'array',
    ];

    public function jobCard(): BelongsTo
    {
        return $this->belongsTo(JobCard::class, 'job_card_id');
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_user_id');
    }

    /**
     * @return array<string, string>
     */
    public static function types(): array
    {
        return [
            self::TYPE_CREATED => 'Created',
            self::TYPE_STATUS_CHANGED => 'Status Changed',
            self::TYPE_ADVISOR_CHANGED => 'Advisor Changed',
            self::TYPE_TECHNICIAN_CHANGED => 'Technician Changed',
            self::TYPE_STAGE_CHANGED => 'Stage Changed',
            self::TYPE_PENDING_REASON_CHANGED => 'Pending Reason Changed',
            self::TYPE_COMPLAINT_ADDED => 'Complaint Added',
            self::TYPE_COMPLAINT_RESOLVED => 'Complaint Resolved',
            self::TYPE_INSPECTION_STARTED => 'Inspection Started',
            self::TYPE_INSPECTION_COMPLETED => 'Inspection Completed',
            self::TYPE_WORK_ORDER_ASSIGNED => 'Work Order Assigned',
            self::TYPE_WORK_ORDER_STARTED => 'Work Order Started',
            self::TYPE_WORK_ORDER_HELD => 'Work Order On Hold',
            self::TYPE_WORK_ORDER_COMPLETED => 'Work Order Completed',
            self::TYPE_FINDING_RECORDED => 'Technician Finding Recorded',
            self::TYPE_CANCELLED => 'Job Card Cancelled',
            self::TYPE_APPOINTMENT_BOOKED => 'Appointment Booked',
            self::TYPE_VEHICLE_ARRIVED => 'Vehicle Arrived',
            self::TYPE_VEHICLE_DEPARTED => 'Vehicle Departed',
            self::TYPE_PICKUP_SCHEDULED => 'Pickup / Drop Scheduled',
            self::TYPE_PARTS_INQUIRY_RAISED => 'Parts Inquiry Raised',
            self::TYPE_DOCUMENT_REQUESTED => 'Document Requested',
            self::TYPE_DOCUMENT_RECEIVED => 'Document Received',
        ];
    }

    public function customerVehicle(): BelongsTo
    {
        return $this->belongsTo(CustomerVehicleMaster::class, 'customer_vehicle_id');
    }
}
