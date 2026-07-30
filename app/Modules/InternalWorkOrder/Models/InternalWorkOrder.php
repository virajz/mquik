<?php

namespace App\Modules\InternalWorkOrder\Models;

use App\Concerns\Auditable;
use App\Concerns\Searchable;
use App\Modules\EmployeeMaster\Models\EmployeeMaster;
use App\Modules\InternalWorkOrder\Database\Factories\InternalWorkOrderFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InternalWorkOrder extends Model
{
    use Auditable;
    use HasFactory;
    use Searchable;

    public const STATUS_REQUESTED = 'requested';

    public const STATUS_UNDER_REVIEW = 'under_review';

    public const STATUS_ON_HOLD = 'on_hold';

    public const STATUS_IN_PROGRESS = 'in_progress';

    public const STATUS_RESOLVED = 'resolved';

    public const STATUS_CANCELLED = 'cancelled';

    protected $table = 'internal_work_orders';

    protected $guarded = [];

    protected $casts = [
        'due_at' => 'datetime',
        'complaint_at' => 'datetime',
        'assigned_at' => 'datetime',
        'work_started_at' => 'datetime',
        'resolved_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    protected static array $searchableFields = ['iwo_no', 'title', 'description', 'notes'];

    protected static function newFactory(): InternalWorkOrderFactory
    {
        return InternalWorkOrderFactory::new();
    }

    protected static function booted(): void
    {
        static::created(function (self $row) {
            if ($row->iwo_no === null) {
                $row->forceFill([
                    'iwo_no' => 'IWO-'.str_pad((string) $row->id, 5, '0', STR_PAD_LEFT),
                ])->saveQuietly();
            }
        });
    }

    /** Statuses that count as still-open (not resolved / cancelled). @return list<string> */
    public static function openStatuses(): array
    {
        return [self::STATUS_REQUESTED, self::STATUS_UNDER_REVIEW, self::STATUS_ON_HOLD, self::STATUS_IN_PROGRESS];
    }

    /** @return array<string, string> */
    public static function statuses(): array
    {
        return [
            self::STATUS_REQUESTED => 'Requested',
            self::STATUS_UNDER_REVIEW => 'Under Review',
            self::STATUS_ON_HOLD => 'On Hold',
            self::STATUS_IN_PROGRESS => 'In Progress',
            self::STATUS_RESOLVED => 'Resolved',
            self::STATUS_CANCELLED => 'Cancelled',
        ];
    }

    /** @return array<string, string> */
    public static function types(): array
    {
        return [
            'raise_complaint' => 'Raise Complaint',
            'raise_request' => 'Raise Request',
            'work_order' => 'Work Order',
            'suggestion' => 'Suggestion',
        ];
    }

    /** @return array<string, string> */
    public static function categories(): array
    {
        return [
            'cctv' => 'CCTV',
            'biometric' => 'Biometric',
            'software' => 'Software',
            'hardware' => 'Hardware',
            'networking' => 'Networking',
            'account_audit' => 'Account Audit',
            'equipment_tool' => 'Equipment / Tool',
            'spare_parts' => 'Spare Parts',
            'safety' => 'Safety',
            'housekeeping' => 'Housekeeping',
            'electrical' => 'Electrical',
            'plumbing' => 'Plumbing',
            'security' => 'Security',
            'vehicle' => 'Vehicle',
            'building_maintenance' => 'Building Maintenance',
            'administrative_task' => 'Administrative Task',
            'compliance' => 'Compliance',
            'training' => 'Training',
            'other' => 'Other',
        ];
    }

    /** @return array<string, string> */
    public static function priorities(): array
    {
        return ['normal' => 'Normal', 'medium' => 'Medium', 'high' => 'High'];
    }

    /** @return array<string, string> */
    public static function departments(): array
    {
        return [
            'hr' => 'HR',
            'account' => 'Account',
            'it' => 'IT',
            'crm' => 'CRM',
            'stores' => 'Stores',
            'security' => 'Security',
            'housekeeping' => 'Housekeeping',
            'management' => 'Management',
        ];
    }

    /** @return array<string, string> */
    public static function responses(): array
    {
        return [
            'work_order_accepted' => 'Work Order Accepted',
            'more_info_required' => 'More Information Required',
            'out_of_scope' => 'Out of Scope',
            'site_inspection_required' => 'Site Inspection Required',
            'photo_required' => 'Photo Required',
            'video_required' => 'Video Required',
            'material_not_available' => 'Material Not Available',
            'spare_parts_awaited' => 'Spare Parts Awaited',
            'tool_not_available' => 'Tool Not Available',
            'manpower_not_available' => 'Manpower Not Available',
            'waiting_for_approval' => 'Waiting for Approval',
            'successfully_completed' => 'Successfully Completed',
            'temporary_resolution' => 'Temporary Resolution',
        ];
    }

    /** @return array<string, string> */
    public static function followUpModes(): array
    {
        return [
            'whatsapp' => 'WhatsApp',
            'email' => 'Email',
            'sms' => 'SMS',
            'mobile_app' => 'Mobile App',
            'telephonic_call' => 'Telephonic Call',
            'physical_visit' => 'Physical Visit',
        ];
    }

    /** @return array<string, string> */
    public static function escalations(): array
    {
        return [
            'escalated_to_hr' => 'Escalated to HR',
            'escalated_to_owner' => 'Escalated to Owner',
        ];
    }

    /** @return array<string, string> */
    public static function rootCauses(): array
    {
        return [
            'human_error' => 'Human Error',
            'process_failure' => 'Process Failure',
            'equipment_failure' => 'Equipment Failure',
            'system_error' => 'System Error',
            'training_gap' => 'Training Gap',
            'external_factor' => 'External Factor',
            'other' => 'Other',
        ];
    }

    /** @return array<string, string> */
    public static function correctiveActions(): array
    {
        return [
            'repair' => 'Repair',
            'replacement' => 'Replacement',
            'retraining' => 'Retraining',
            'process_change' => 'Process Change',
            'policy_update' => 'Policy Update',
            'warning' => 'Warning',
            'preventive_maintenance' => 'Preventive Maintenance',
            'other' => 'Other',
        ];
    }

    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(EmployeeMaster::class, 'requested_by_id');
    }

    public function requestedTo(): BelongsTo
    {
        return $this->belongsTo(EmployeeMaster::class, 'requested_to_id');
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(EmployeeMaster::class, 'assigned_to_id');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(InternalWorkOrderAttachment::class, 'internal_work_order_id')->orderBy('sequence_no');
    }
}
