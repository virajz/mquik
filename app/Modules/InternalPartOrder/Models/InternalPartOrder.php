<?php

namespace App\Modules\InternalPartOrder\Models;

use App\Concerns\Auditable;
use App\Concerns\Searchable;
use App\Modules\CustomerMaster\Models\CustomerMaster;
use App\Modules\CustomerVehicleMaster\Models\CustomerVehicleMaster;
use App\Modules\EmployeeMaster\Models\EmployeeMaster;
use App\Modules\InternalPartOrder\Database\Factories\InternalPartOrderFactory;
use App\Modules\IpoCancellationReasonMaster\Models\IpoCancellationReasonMaster;
use App\Modules\IpoRejectionReasonMaster\Models\IpoRejectionReasonMaster;
use App\Modules\JobCard\Models\JobCard;
use App\Modules\SalesEstimate\Models\SalesEstimate;
use App\Modules\ServiceTypeMaster\Models\ServiceTypeMaster;
use App\Modules\WorkshopDepartmentMaster\Models\WorkshopDepartmentMaster;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InternalPartOrder extends Model
{
    use Auditable;
    use HasFactory;
    use Searchable;

    protected $table = 'internal_part_orders';

    protected $guarded = [];

    protected $casts = [
        'requested_at' => 'datetime',
        'approved_at' => 'datetime',
        'issued_at' => 'datetime',
    ];

    protected static array $searchableFields = ['order_no', 'notes'];

    protected static function newFactory(): InternalPartOrderFactory
    {
        return InternalPartOrderFactory::new();
    }

    protected static function booted(): void
    {
        static::created(function (self $row) {
            if ($row->order_no === null) {
                $row->forceFill([
                    'order_no' => 'IPO-'.str_pad((string) $row->id, 5, '0', STR_PAD_LEFT),
                ])->saveQuietly();
            }
        });
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
        return $this->belongsTo(WorkshopDepartmentMaster::class, 'department_id');
    }

    public function serviceType(): BelongsTo
    {
        return $this->belongsTo(ServiceTypeMaster::class, 'service_type_id');
    }

    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(EmployeeMaster::class, 'requested_by_id');
    }

    public function technician(): BelongsTo
    {
        return $this->belongsTo(EmployeeMaster::class, 'technician_id');
    }

    public function storeIncharge(): BelongsTo
    {
        return $this->belongsTo(EmployeeMaster::class, 'store_incharge_id');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(EmployeeMaster::class, 'approved_by_id');
    }

    public function rejectionReason(): BelongsTo
    {
        return $this->belongsTo(IpoRejectionReasonMaster::class, 'rejection_reason_id');
    }

    public function cancellationReason(): BelongsTo
    {
        return $this->belongsTo(IpoCancellationReasonMaster::class, 'cancellation_reason_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(InternalPartOrderItem::class, 'internal_part_order_id')->orderBy('sequence_no');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(InternalPartOrderAttachment::class, 'internal_part_order_id');
    }

    /** @return array<string, string> */
    public static function ipoTypes(): array
    {
        return [
            'job_card_requirement' => 'Job Card Requirement',
            'reserved_parts' => 'Reserved Parts Order',
            'testing_observation' => 'Testing & Observation',
            'general_use' => 'Workshop General Use',
        ];
    }

    /** @return array<string, string> */
    public static function priorities(): array
    {
        return [
            'normal' => 'Normal',
            'urgent' => 'Urgent',
            'breakdown' => 'Breakdown',
            'critical' => 'Critical',
        ];
    }

    /** @return array<string, string> */
    public static function statuses(): array
    {
        return [
            'draft' => 'Draft',
            'requested' => 'Requested',
            'approved' => 'Approved',
            'processing' => 'Processing',
            'partially_issued' => 'Partially Issued',
            'fully_issued' => 'Fully Issued',
            'cancelled' => 'Cancelled',
            'closed' => 'Closed',
        ];
    }

    /** @return array<string, string> */
    public static function approvalAuthorities(): array
    {
        return [
            'store_manager' => 'Store Manager',
            'service_advisor' => 'Service Advisor',
            'workshop_manager' => 'Workshop Manager',
            'owner' => 'Owner',
            'admin' => 'Admin',
        ];
    }

    /** @return array<string, string> */
    public static function approvalStatuses(): array
    {
        return [
            'pending' => 'Pending',
            'approved' => 'Approved',
            'rejected' => 'Rejected',
        ];
    }

    /** @return array<string, string> */
    public static function stockStatuses(): array
    {
        return [
            'available' => 'Available',
            'reserved' => 'Reserved',
            'issued' => 'Issued',
            'out_of_stock' => 'Out of Stock',
            'in_transit' => 'In Transit',
        ];
    }

    /** @return array<string, string> */
    public static function issueStatuses(): array
    {
        return [
            'pending' => 'Pending',
            'issued' => 'Issued',
            'partially_issued' => 'Partially Issued',
            'returned' => 'Returned',
            'backorder' => 'Backorder',
        ];
    }

    /** @return array<string, string> */
    public static function returnStatuses(): array
    {
        return [
            'pending' => 'Pending',
            'accepted' => 'Accepted',
            'rejected' => 'Rejected',
        ];
    }
}
