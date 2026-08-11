<?php

namespace App\Modules\GatePassApproval\Models;

use App\Concerns\Auditable;
use App\Concerns\Searchable;
use App\Modules\CustomerMaster\Models\CustomerMaster;
use App\Modules\CustomerVehicleMaster\Models\CustomerVehicleMaster;
use App\Modules\EmployeeMaster\Models\EmployeeMaster;
use App\Modules\FollowUpModeMaster\Models\FollowUpModeMaster;
use App\Modules\GatePassApproval\Database\Factories\GatePassApprovalFactory;
use App\Modules\JobCard\Models\JobCard;
use App\Modules\WorkshopDepartmentMaster\Models\WorkshopDepartmentMaster;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GatePassApproval extends Model
{
    use Auditable;
    use HasFactory;
    use Searchable;

    /** Approval-matrix threshold: at or below → advisor/cashier, above → admin. */
    public const MATRIX_THRESHOLD = 25000;

    public const STATUS_REQUESTED = 'requested';

    public const STATUS_UNDER_REVIEW = 'under_review';

    public const STATUS_ON_HOLD = 'on_hold';

    public const STATUS_PARTIAL_APPROVED = 'partial_pending_approved';

    public const STATUS_FULL_APPROVED = 'full_pending_approved';

    public const STATUS_REJECTED = 'rejected';

    public const STATUS_CANCELLED = 'cancelled';

    public const AUTHORITY_ADVISOR = 'service_advisor_cashier';

    public const AUTHORITY_ADMIN = 'admin_hr_owner';

    protected $table = 'gate_pass_approvals';

    protected $guarded = [];

    protected $casts = [
        'invoice_amount' => 'decimal:2',
        'receipt_amount' => 'decimal:2',
        'outstanding_amount' => 'decimal:2',
        'credit_exposure' => 'decimal:2',
        'requested_at' => 'datetime',
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    protected static array $searchableFields = ['approval_no', 'invoice_reference', 'notes', 'jobCard.job_card_no'];

    protected static function newFactory(): GatePassApprovalFactory
    {
        return GatePassApprovalFactory::new();
    }

    protected static function booted(): void
    {
        static::created(function (self $row) {
            if ($row->approval_no === null) {
                $row->forceFill(['approval_no' => 'GPA-'.str_pad((string) $row->id, 5, '0', STR_PAD_LEFT)])->saveQuietly();
            }
        });
    }

    /** The approval authority required for a given credit exposure, per the matrix. */
    public static function authorityForAmount(?float $amount): string
    {
        return ($amount ?? 0) > self::MATRIX_THRESHOLD ? self::AUTHORITY_ADMIN : self::AUTHORITY_ADVISOR;
    }

    /** Statuses that count as an approved (delivered-with-outstanding) gate pass. @return list<string> */
    public static function approvedStatuses(): array
    {
        return [self::STATUS_PARTIAL_APPROVED, self::STATUS_FULL_APPROVED];
    }

    /** @return array<string, string> */
    public static function statuses(): array
    {
        return [
            self::STATUS_REQUESTED => 'Requested',
            self::STATUS_UNDER_REVIEW => 'Under Review',
            self::STATUS_ON_HOLD => 'On Hold',
            self::STATUS_PARTIAL_APPROVED => 'Partial Pending Approved',
            self::STATUS_FULL_APPROVED => 'Full Pending Approved',
            self::STATUS_REJECTED => 'Rejected',
            self::STATUS_CANCELLED => 'Cancelled',
        ];
    }

    /** @return array<string, string> */
    public static function priorities(): array
    {
        return ['normal' => 'Normal', 'high' => 'High', 'emergency' => 'Emergency'];
    }

    /** @return array<string, string> */
    public static function creditTypes(): array
    {
        return [
            'partial_pending' => 'Partial Pending',
            'full_pending' => 'Full Pending',
            'post_dated_cheque' => 'Post Dated Cheque',
            'without_ins_do' => 'Without Ins. DO',
        ];
    }

    /** @return array<string, string> */
    public static function creditReasons(): array
    {
        return [
            'corporate_credit' => 'Corporate Credit Facility',
            'vip_credit' => 'VIP Credit Facility',
            'friend_circle' => 'Friend Circle',
            'relative' => 'Relative',
            'neighbour' => 'Neighbour',
            'regular_customer' => 'Regular Customer',
            'staff' => 'Staff',
            'cheque_under_collection' => 'Cheque Under Collection',
            'medical_emergency' => 'Medical Emergency',
            'early_delivery_po' => 'Early Delivery Against PO',
            'bank_strike' => 'Bank Strike',
        ];
    }

    /** @return array<string, string> */
    public static function customerCommitments(): array
    {
        return ['verbal' => 'Verbal Commitment', 'written' => 'Written Commitment'];
    }

    /** @return array<string, string> */
    public static function securityDeposits(): array
    {
        return ['cheque' => 'Cheque', 'cash' => 'Cash'];
    }

    /** @return array<string, string> */
    public static function riskTypes(): array
    {
        return ['low' => 'Low', 'medium' => 'Medium', 'high' => 'High'];
    }

    /** @return array<string, string> */
    public static function riskCategories(): array
    {
        return [
            'high_outstanding' => 'High Outstanding Amt',
            'previous_outstanding' => 'Previous Outstanding Exists',
            'new_customer' => 'New Customer',
            'wrong_behaviour' => 'Wrong Behaviour with Staff',
            'customer_dispute' => 'Customer Dispute',
        ];
    }

    /** @return array<string, string> */
    public static function approvalAuthorities(): array
    {
        return [
            self::AUTHORITY_ADVISOR => 'Service Advisor / Cashier',
            self::AUTHORITY_ADMIN => 'Admin / HR / Owner',
        ];
    }

    /** @return array<string, string> */
    public static function cancellationReasons(): array
    {
        return [
            'duplicate_entry' => 'Duplicate Entry',
            'wrong_customer' => 'Wrong Customer Selected',
            'wrong_vehicle' => 'Wrong Vehicle Selected',
        ];
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(WorkshopDepartmentMaster::class, 'workshop_department_id');
    }

    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(EmployeeMaster::class, 'requested_by_id');
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

    public function followUpMode(): BelongsTo
    {
        return $this->belongsTo(FollowUpModeMaster::class, 'follow_up_mode_id');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(GatePassApprovalAttachment::class, 'gate_pass_approval_id')->orderBy('sequence_no');
    }
}
