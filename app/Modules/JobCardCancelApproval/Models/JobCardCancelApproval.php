<?php

namespace App\Modules\JobCardCancelApproval\Models;

use App\Concerns\Auditable;
use App\Concerns\Searchable;
use App\Modules\EmployeeMaster\Models\EmployeeMaster;
use App\Modules\FollowUpModeMaster\Models\FollowUpModeMaster;
use App\Modules\JobCard\Models\JobCard;
use App\Modules\JobCardCancelApproval\Database\Factories\JobCardCancelApprovalFactory;
use App\Modules\JobCardCancelReasonMaster\Models\JobCardCancelReasonMaster;
use App\Modules\ServiceTypeMaster\Models\ServiceTypeMaster;
use App\Modules\WorkshopDepartmentMaster\Models\WorkshopDepartmentMaster;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JobCardCancelApproval extends Model
{
    use Auditable;
    use HasFactory;
    use Searchable;

    public const STATUS_PENDING = 'pending';

    public const STATUS_REQUESTED = 'requested';

    public const STATUS_UNDER_REVIEW = 'under_review';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_REJECTED = 'rejected';

    public const STATUS_CANCELLED = 'cancelled';

    public const STATUS_REVERSED = 'reversed';

    protected $table = 'job_card_cancel_approvals';

    protected $guarded = [];

    protected $casts = [
        'impacts' => 'array',
        'decided_at' => 'datetime',
    ];

    protected static array $searchableFields = ['approval_no', 'notes'];

    protected static function newFactory(): JobCardCancelApprovalFactory
    {
        return JobCardCancelApprovalFactory::new();
    }

    protected static function booted(): void
    {
        static::created(function (self $row) {
            if ($row->approval_no === null) {
                $row->forceFill([
                    'approval_no' => 'JCA-'.str_pad((string) $row->id, 5, '0', STR_PAD_LEFT),
                ])->saveQuietly();
            }
        });
    }

    /** @return array<string, string> */
    public static function statuses(): array
    {
        return [
            self::STATUS_PENDING => 'Pending',
            self::STATUS_REQUESTED => 'Requested',
            self::STATUS_UNDER_REVIEW => 'Under Review',
            self::STATUS_APPROVED => 'Approved',
            self::STATUS_REJECTED => 'Rejected',
            self::STATUS_CANCELLED => 'Cancelled',
            self::STATUS_REVERSED => 'Reversed',
        ];
    }

    /** @return array<string, string> */
    public static function cancellationTypes(): array
    {
        return [
            'wrong_entry' => 'Wrong Entry',
            'customer_rejection' => 'Customer Rejection',
            'insurance_rejection' => 'Insurance Rejection',
            'duplicate' => 'Duplicate Job Card',
            'internal_error' => 'Internal Error',
            'operational_issue' => 'Operational Issue',
        ];
    }

    /** @return array<string, string> */
    public static function approvalLevels(): array
    {
        return [
            'l1_advisor' => 'L1 – Service Advisor',
            'l2_store' => 'L2 – Store Manager',
            'l3_accounts' => 'L3 – Accounts Manager',
            'l4_workshop' => 'L4 – Workshop Manager / Owner',
        ];
    }

    /** Downstream effects of a cancellation (multi-select). @return array<string, string> */
    public static function impactOptions(): array
    {
        return [
            'return_part_to_vendor' => 'Return Part to Vendor',
            'reverse_parts_in_stock' => 'Reverse Parts in Stock',
            'issue_part_in_consumption' => 'Issue Part in Consumption',
            'return_labour_claim_warranty' => 'Return Labour & Claim Warranty',
            'refund_payment' => 'Refund Payment',
            'payment_adjustment_next_job' => 'Payment Adjustment Against Next Job',
        ];
    }

    /** @return array<string, string> */
    public static function rejectionReasons(): array
    {
        return [
            'insufficient_reason' => 'Insufficient Reason',
            'financial_impact' => 'Financial Impact Too High',
            'work_completed' => 'Work Already Completed',
            'management_decision' => 'Management Decision',
        ];
    }

    /** @return array<string, string> */
    public static function refundStatuses(): array
    {
        return [
            'pending' => 'Pending',
            'processed' => 'Processed',
            'completed' => 'Completed',
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

    public function serviceType(): BelongsTo
    {
        return $this->belongsTo(ServiceTypeMaster::class, 'service_type_id');
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(EmployeeMaster::class, 'employee_id');
    }

    public function cancelReason(): BelongsTo
    {
        return $this->belongsTo(JobCardCancelReasonMaster::class, 'cancel_reason_id');
    }

    public function followUpMode(): BelongsTo
    {
        return $this->belongsTo(FollowUpModeMaster::class, 'follow_up_mode_id');
    }
}
