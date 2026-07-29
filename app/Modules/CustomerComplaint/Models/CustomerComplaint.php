<?php

namespace App\Modules\CustomerComplaint\Models;

use App\Concerns\Auditable;
use App\Concerns\Searchable;
use App\Modules\CustomerComplaint\Database\Factories\CustomerComplaintFactory;
use App\Modules\CustomerMaster\Models\CustomerMaster;
use App\Modules\CustomerVehicleMaster\Models\CustomerVehicleMaster;
use App\Modules\EmployeeMaster\Models\EmployeeMaster;
use App\Modules\JobCard\Models\JobCard;
use App\Modules\ServiceTypeMaster\Models\ServiceTypeMaster;
use App\Modules\WorkshopDepartmentMaster\Models\WorkshopDepartmentMaster;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CustomerComplaint extends Model
{
    use Auditable;
    use HasFactory;
    use Searchable;

    public const STATUS_UNDER_INVESTIGATION = 'under_investigation';

    public const STATUS_PENDING_CUSTOMER = 'pending_customer_response';

    public const STATUS_PENDING_VENDOR = 'pending_vendor_response';

    public const STATUS_RESOLVED = 'resolved';

    public const STATUS_REJECTED = 'rejected';

    public const STATUS_CANCELLED = 'cancelled';

    protected $table = 'customer_complaints';

    protected $guarded = [];

    protected $casts = [
        'achieved_score' => 'integer',
        'recommended_score' => 'integer',
        'opened_at' => 'datetime',
        'assigned_at' => 'datetime',
        'investigation_start_at' => 'datetime',
        'investigation_complete_at' => 'datetime',
        'closed_at' => 'datetime',
        'reopened_at' => 'datetime',
    ];

    protected static array $searchableFields = ['complaint_no', 'invoice_reference', 'description', 'notes'];

    protected static function newFactory(): CustomerComplaintFactory
    {
        return CustomerComplaintFactory::new();
    }

    protected static function booted(): void
    {
        static::created(function (self $row) {
            if ($row->complaint_no === null) {
                $row->forceFill(['complaint_no' => 'CMP-'.str_pad((string) $row->id, 5, '0', STR_PAD_LEFT)])->saveQuietly();
            }
        });
    }

    /** Open (not closed) statuses. @return list<string> */
    public static function openStatuses(): array
    {
        return [self::STATUS_UNDER_INVESTIGATION, self::STATUS_PENDING_CUSTOMER, self::STATUS_PENDING_VENDOR];
    }

    /** @return array<string, string> */
    public static function statuses(): array
    {
        return [
            self::STATUS_UNDER_INVESTIGATION => 'Under Investigation',
            self::STATUS_PENDING_CUSTOMER => 'Pending Customer Response',
            self::STATUS_PENDING_VENDOR => 'Pending Vendor Response',
            self::STATUS_RESOLVED => 'Resolved',
            self::STATUS_REJECTED => 'Rejected',
            self::STATUS_CANCELLED => 'Cancelled',
        ];
    }

    /** @return array<string, string> */
    public static function complaintTypes(): array
    {
        return [
            'service_quality' => 'Service / Repair Quality',
            'incomplete_work' => 'Incomplete Work',
            'repeat_job' => 'Repeat Job',
            'wrong_part_install' => 'Wrong Part Install',
            'defective_part' => 'Defective Part',
            'parts_not_replaced' => 'Parts Not Replaced',
            'spares_quality' => 'Spares Quality',
            'invoice_dispute' => 'Invoice Dispute',
            'staff_behavior' => 'Staff Behavior',
            'delivery_delay' => 'Delivery Delay',
            'vehicle_damage' => 'Vehicle Damage',
            'missing_item' => 'Missing Item',
            'vehicle_breakdown' => 'Vehicle Breakdown',
            'noise_vibration' => 'Noise / Vibration',
            'claim_delay' => 'Claim Delay',
            'claim_rejection' => 'Claim Rejection',
        ];
    }

    /** @return array<string, string> */
    public static function complaintSources(): array
    {
        return [
            'physical_visit' => 'Physical Visit',
            'phone_call' => 'Phone Call',
            'whatsapp' => 'WhatsApp',
            'email' => 'Email',
            'website' => 'Website',
            'mobile_app' => 'Mobile App',
            'social_media' => 'Social Media',
            'feedback' => 'Feedback',
            'google_review' => 'Google Review',
        ];
    }

    /** @return array<string, string> */
    public static function priorities(): array
    {
        return ['normal' => 'Normal', 'medium' => 'Medium', 'high' => 'High'];
    }

    /** @return array<string, string> */
    public static function assignments(): array
    {
        return [
            'crm_executive' => 'CRM Executive',
            'service_advisor' => 'Service Advisor',
            'store_incharge' => 'Store In-charge',
            'billing_executive' => 'Billing Executive',
            'admin_hr_owner' => 'Admin / HR / Owner',
        ];
    }

    /** @return array<string, string> */
    public static function rootCauses(): array
    {
        return [
            'wrong_diagnosis' => 'Wrong Diagnosis',
            'human_error' => 'Human Error',
            'training_gap' => 'Training Gap',
            'process_failure' => 'Process Failure',
            'system_error' => 'System Error',
            'parts_failure' => 'Parts Failure',
            'communication_gap' => 'Communication Gap',
            'wrong_part_replaced' => 'Wrong Part Replaced',
            'customer_misunderstanding' => 'Customer Misunderstanding',
        ];
    }

    /** @return array<string, string> */
    public static function resolutionTypes(): array
    {
        return [
            'rework' => 'Rework',
            'part_replace' => 'Part Replace',
            'cn_issued' => 'CN Issued',
            'refund' => 'Refund',
            'discount' => 'Discount',
            'apology_issued' => 'Apology Issued',
            'warranty_settlement' => 'Warranty Settlement',
        ];
    }

    /** @return array<string, string> */
    public static function reopenReasons(): array
    {
        return [
            'issue_not_resolved' => 'Issue Not Resolved',
            'partial_resolution' => 'Partial Resolution',
            'repeat_job' => 'Repeat Job',
        ];
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(WorkshopDepartmentMaster::class, 'workshop_department_id');
    }

    public function serviceType(): BelongsTo
    {
        return $this->belongsTo(ServiceTypeMaster::class, 'service_type_id');
    }

    public function openedBy(): BelongsTo
    {
        return $this->belongsTo(EmployeeMaster::class, 'opened_by_id');
    }

    public function advisor(): BelongsTo
    {
        return $this->belongsTo(EmployeeMaster::class, 'advisor_id');
    }

    public function technician(): BelongsTo
    {
        return $this->belongsTo(EmployeeMaster::class, 'technician_id');
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

    public function attachments(): HasMany
    {
        return $this->hasMany(CustomerComplaintAttachment::class, 'customer_complaint_id')->orderBy('sequence_no');
    }
}
