<?php

namespace App\Modules\IpoCancelApproval\Models;

use App\Concerns\Auditable;
use App\Concerns\Searchable;
use App\Modules\CustomerMaster\Models\CustomerMaster;
use App\Modules\CustomerVehicleMaster\Models\CustomerVehicleMaster;
use App\Modules\EmployeeMaster\Models\EmployeeMaster;
use App\Modules\InternalPartOrder\Models\InternalPartOrder;
use App\Modules\IpoCancelApproval\Database\Factories\IpoCancelApprovalFactory;
use App\Modules\JobCard\Models\JobCard;
use App\Modules\ServiceTypeMaster\Models\ServiceTypeMaster;
use App\Modules\SpareMaster\Models\SpareMaster;
use App\Modules\UnitOfMeasureMaster\Models\UnitOfMeasureMaster;
use App\Modules\WorkshopDepartmentMaster\Models\WorkshopDepartmentMaster;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class IpoCancelApproval extends Model
{
    use Auditable;
    use HasFactory;
    use Searchable;

    public const STATUS_UNDER_REVIEW = 'under_review';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_REJECTED = 'rejected';

    public const STATUS_CANCELLED = 'cancelled';

    public const STATUS_REVERSED = 'reversed';

    public const STATUS_NEEDS_CLARIFICATION = 'needs_clarification';

    protected $table = 'ipo_cancel_approvals';

    protected $guarded = [];

    protected $casts = [
        'impacts' => 'array',
        'quantity' => 'decimal:2',
        'decided_at' => 'datetime',
    ];

    protected static array $searchableFields = ['cancel_no', 'notes'];

    protected static function newFactory(): IpoCancelApprovalFactory
    {
        return IpoCancelApprovalFactory::new();
    }

    protected static function booted(): void
    {
        static::created(function (self $row) {
            if ($row->cancel_no === null) {
                $row->forceFill(['cancel_no' => 'ICR-'.str_pad((string) $row->id, 5, '0', STR_PAD_LEFT)])->saveQuietly();
            }
        });
    }

    /** @return array<string, string> */
    public static function statuses(): array
    {
        return [
            self::STATUS_UNDER_REVIEW => 'Under Review @ Vendor',
            self::STATUS_APPROVED => 'Approved',
            self::STATUS_REJECTED => 'Rejected',
            self::STATUS_CANCELLED => 'Cancelled',
            self::STATUS_REVERSED => 'Reversed',
            self::STATUS_NEEDS_CLARIFICATION => 'Needs Verbal Clarification',
        ];
    }

    /** @return array<string, string> */
    public static function cancellationReasons(): array
    {
        return [
            'excess_qty' => 'Excess Qty',
            'wrong_part_ordered' => 'Wrong Part Number Order',
            'wrong_part_supplied' => 'Wrong Part Number Supplied',
            'duplicate' => 'Duplicate Order',
            'budget_issue' => 'Customer Budget Issue',
            'no_longer_required' => 'Part No Longer Required',
            'customer_refused' => 'Customer Refused',
            'alternative_used' => 'Alternative Part Used',
        ];
    }

    /** @return array<string, string> */
    public static function impactOptions(): array
    {
        return [
            'return_to_vendor' => 'Return Part to Vendor',
            'reverse_stock' => 'Reverse Parts in Stock',
            'issue_consumption' => 'Issue Part in Consumption & Adjust Stock',
            'cancel_po' => 'Cancel Purchase Order',
            'refund_payment' => 'Refund Payment',
            'adjust_next_job' => 'Payment Adjustment Against Next Job',
        ];
    }

    /** @return array<string, string> */
    public static function categories(): array
    {
        return [
            'operational_error' => 'Operational Error',
            'customer_driven' => 'Customer Driven',
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
            'returned' => 'Returned',
            'rejected' => 'Rejected',
        ];
    }

    /** @return array<string, string> */
    public static function returnTypes(): array
    {
        return [
            'used_in_vehicle' => 'Used in Vehicle',
            'not_used' => 'Not Used in Vehicle',
            'full_return' => 'Full Return',
            'partial_return' => 'Partial Return',
            'damaged_return' => 'Damaged Return',
        ];
    }

    /** @return array<string, string> */
    public static function rejectionReasons(): array
    {
        return [
            'invalid_reason' => 'Invalid Reason',
            'used_part' => 'Used Part',
            'damaged_part' => 'Damaged Part',
            'wrong_part' => 'Wrong Part No.',
            'job_in_progress' => 'Job In Progress',
            'financial_impact' => 'Financial Impact Too High',
            'in_transit' => 'In Transit',
        ];
    }

    /** @return array<string, string> */
    public static function approvalLevels(): array
    {
        return [
            'advisor' => 'Service Advisor',
            'store_manager' => 'Store Manager',
            'workshop_manager' => 'Workshop Manager / Admin / Owner',
        ];
    }

    public function jobCard(): BelongsTo
    {
        return $this->belongsTo(JobCard::class, 'job_card_id');
    }

    public function internalPartOrder(): BelongsTo
    {
        return $this->belongsTo(InternalPartOrder::class, 'internal_part_order_id');
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

    public function spare(): BelongsTo
    {
        return $this->belongsTo(SpareMaster::class, 'spare_id');
    }

    public function uom(): BelongsTo
    {
        return $this->belongsTo(UnitOfMeasureMaster::class, 'uom_id');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(IpoCancelApprovalAttachment::class, 'ipo_cancel_approval_id')->orderBy('sequence_no');
    }
}
