<?php

namespace App\Modules\InternalPartsInquiry\Models;

use App\Concerns\Auditable;
use App\Concerns\Searchable;
use App\Modules\CustomerMaster\Models\CustomerMaster;
use App\Modules\CustomerVehicleMaster\Models\CustomerVehicleMaster;
use App\Modules\EmployeeMaster\Models\EmployeeMaster;
use App\Modules\InternalPartsInquiry\Database\Factories\InternalPartsInquiryFactory;
use App\Modules\IpiRejectionReasonMaster\Models\IpiRejectionReasonMaster;
use App\Modules\JobCard\Models\JobCard;
use App\Modules\PriorityMaster\Models\PriorityMaster;
use App\Modules\VendorMaster\Models\VendorMaster;
use App\Modules\WorkshopDepartmentMaster\Models\WorkshopDepartmentMaster;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InternalPartsInquiry extends Model
{
    use Auditable;
    use HasFactory;
    use Searchable;

    public const STATUS_PENDING = 'pending';

    public const STATUS_IN_PROGRESS = 'in_progress';

    public const STATUS_PARTIALLY_AVAILABLE = 'partially_available';

    public const STATUS_FULLY_AVAILABLE = 'fully_available';

    public const STATUS_NOT_AVAILABLE = 'not_available';

    public const STATUS_ALTERNATIVE_SUGGESTED = 'alternative_suggested';

    public const STATUS_ORDERED = 'ordered';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_CANCELLED = 'cancelled';

    protected $table = 'internal_parts_inquiries';

    protected $guarded = [];

    protected $casts = [
        'requested_at' => 'datetime',
        'needed_by' => 'datetime',
        'tat_custom_days' => 'integer',
    ];

    protected static array $searchableFields = ['ipi_no', 'notes'];

    protected static function newFactory(): InternalPartsInquiryFactory
    {
        return InternalPartsInquiryFactory::new();
    }

    protected static function booted(): void
    {
        static::created(function (self $row) {
            if ($row->ipi_no === null) {
                $row->forceFill([
                    'ipi_no' => 'IPI-'.str_pad((string) $row->id, 5, '0', STR_PAD_LEFT),
                ])->saveQuietly();
            }
        });
    }

    /** @return array<string, string> */
    public static function statuses(): array
    {
        return [
            self::STATUS_PENDING => 'Pending',
            self::STATUS_IN_PROGRESS => 'In Progress',
            self::STATUS_PARTIALLY_AVAILABLE => 'Partially Available',
            self::STATUS_FULLY_AVAILABLE => 'Fully Available',
            self::STATUS_NOT_AVAILABLE => 'Not Available',
            self::STATUS_ALTERNATIVE_SUGGESTED => 'Alternative Suggested',
            self::STATUS_ORDERED => 'Ordered',
            self::STATUS_COMPLETED => 'Completed',
            self::STATUS_CANCELLED => 'Cancelled',
        ];
    }

    /** @return array<string, string> */
    public static function inquiryTypes(): array
    {
        return [
            'against_job_card' => 'Against Job Card',
            'stock_replenishment' => 'Stock Replenishment',
            'special_order' => 'Special Order',
            'emergency_requirement' => 'Emergency Requirement',
        ];
    }

    /** @return array<string, string> */
    public static function approvalAuthorities(): array
    {
        return [
            'service_advisor' => 'Service Advisor',
            'store_manager' => 'Store Manager',
            'workshop_manager' => 'Workshop Manager',
            'owner' => 'Owner',
            'admin' => 'Admin',
        ];
    }

    /** @return array<string, string> */
    public static function tatOptions(): array
    {
        return [
            'immediate' => 'Immediate',
            'same_day' => 'Same Day',
            'next_day' => 'Next Day',
            'two_three_days' => '2–3 Days',
            'custom' => 'Custom',
        ];
    }

    public function jobCard(): BelongsTo
    {
        return $this->belongsTo(JobCard::class, 'job_card_id');
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(WorkshopDepartmentMaster::class, 'workshop_department_id');
    }

    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(EmployeeMaster::class, 'requested_by_employee_id');
    }

    public function target(): BelongsTo
    {
        return $this->belongsTo(EmployeeMaster::class, 'target_employee_id');
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

    public function priority(): BelongsTo
    {
        return $this->belongsTo(PriorityMaster::class, 'priority_id');
    }

    public function rejectionReason(): BelongsTo
    {
        return $this->belongsTo(IpiRejectionReasonMaster::class, 'rejection_reason_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(InternalPartsInquiryItem::class, 'internal_parts_inquiry_id')->orderBy('sequence_no');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(InternalPartsInquiryAttachment::class, 'internal_parts_inquiry_id')->orderBy('sequence_no');
    }
}
