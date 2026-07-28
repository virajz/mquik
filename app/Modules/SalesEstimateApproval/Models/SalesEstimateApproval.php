<?php

namespace App\Modules\SalesEstimateApproval\Models;

use App\Concerns\Auditable;
use App\Concerns\Searchable;
use App\Modules\CustomerApprovalTypeMaster\Models\CustomerApprovalTypeMaster;
use App\Modules\CustomerMaster\Models\CustomerMaster;
use App\Modules\CustomerVehicleMaster\Models\CustomerVehicleMaster;
use App\Modules\EmployeeMaster\Models\EmployeeMaster;
use App\Modules\FollowUpModeMaster\Models\FollowUpModeMaster;
use App\Modules\InsuranceCompanyMaster\Models\InsuranceCompanyMaster;
use App\Modules\JobCard\Models\JobCard;
use App\Modules\SalesEstimate\Models\SalesEstimate;
use App\Modules\SalesEstimateApproval\Database\Factories\SalesEstimateApprovalFactory;
use App\Modules\ServiceTypeMaster\Models\ServiceTypeMaster;
use App\Modules\SurveyorInspection\Models\SurveyorInspection;
use App\Modules\WorkshopDepartmentMaster\Models\WorkshopDepartmentMaster;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SalesEstimateApproval extends Model
{
    use Auditable;
    use HasFactory;
    use Searchable;

    public const STATUS_SENT = 'sent_for_approval';

    public const STATUS_PARTIALLY_APPROVED = 'partially_approved';

    public const STATUS_FULLY_APPROVED = 'fully_approved';

    public const STATUS_QUERY_RAISED = 'query_raised';

    public const STATUS_REVISED_RESENT = 'revised_resent';

    public const STATUS_NO_RESPONSE = 'no_response';

    public const STATUS_REJECTED = 'rejected';

    public const STATUS_CANCELLED = 'cancelled';

    protected $table = 'sales_estimate_approvals';

    protected $guarded = [];

    protected $casts = [
        'customer_approved_at' => 'datetime',
        'insurance_approved_at' => 'datetime',
        'approved_at' => 'datetime',
        'reminder_custom_days' => 'integer',
    ];

    protected static array $searchableFields = ['approval_no', 'notes'];

    protected static function newFactory(): SalesEstimateApprovalFactory
    {
        return SalesEstimateApprovalFactory::new();
    }

    protected static function booted(): void
    {
        static::created(function (self $row) {
            if ($row->approval_no === null) {
                $row->forceFill([
                    'approval_no' => 'SEA-'.str_pad((string) $row->id, 5, '0', STR_PAD_LEFT),
                ])->saveQuietly();
            }
        });
    }

    /** @return array<string, string> */
    public static function statuses(): array
    {
        return [
            self::STATUS_SENT => 'Sent for Approval',
            self::STATUS_PARTIALLY_APPROVED => 'Partially Approved',
            self::STATUS_FULLY_APPROVED => 'Fully Approved',
            self::STATUS_QUERY_RAISED => 'Query Raised',
            self::STATUS_REVISED_RESENT => 'Revised & Resent',
            self::STATUS_NO_RESPONSE => 'No Response',
            self::STATUS_REJECTED => 'Rejected',
            self::STATUS_CANCELLED => 'Cancelled',
        ];
    }

    /** @return array<string, string> */
    public static function approvalTypes(): array
    {
        return [
            'regular' => 'Regular',
            'supplementary' => 'Supplementary',
            'additional' => 'Additional',
            'revised' => 'Revised',
        ];
    }

    /** @return array<string, string> */
    public static function approvalAuthorisations(): array
    {
        return [
            'insurance' => 'Insurance Approval',
            'customer' => 'Customer Approval',
            'both' => 'Insurance & Customer',
            'internal' => 'Internal Approval',
            'management' => 'Management Approval',
        ];
    }

    /** @return array<string, string> */
    public static function partsBrandPreferences(): array
    {
        return [
            'genuine' => 'Genuine',
            'aftermarket' => 'After Market',
            'any' => 'Any',
        ];
    }

    /** @return array<string, string> */
    public static function rejectionReasons(): array
    {
        return [
            'high_cost' => 'High Cost',
            'customer_budget' => 'Customer Budget Issue',
            'insurance_not_covered' => 'Insurance Not Covered',
            'no_claim' => 'No Claim',
            'additional_damage' => 'Additional Damage',
            'non_accident_damage' => 'Non-Accident Damage',
            'policy_limitation' => 'Policy Limitation',
        ];
    }

    /** @return array<string, string> */
    public static function reminderFrequencies(): array
    {
        return [
            'daily' => 'Daily',
            'every_2_days' => 'Every 2 Days',
            'custom' => 'Custom',
        ];
    }

    public function jobCard(): BelongsTo
    {
        return $this->belongsTo(JobCard::class, 'job_card_id');
    }

    public function salesEstimate(): BelongsTo
    {
        return $this->belongsTo(SalesEstimate::class, 'sales_estimate_id');
    }

    public function surveyorInspection(): BelongsTo
    {
        return $this->belongsTo(SurveyorInspection::class, 'surveyor_inspection_id');
    }

    public function insuranceCompany(): BelongsTo
    {
        return $this->belongsTo(InsuranceCompanyMaster::class, 'insurance_company_id');
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

    public function approvalMode(): BelongsTo
    {
        return $this->belongsTo(CustomerApprovalTypeMaster::class, 'approval_mode_id');
    }

    public function followUpMode(): BelongsTo
    {
        return $this->belongsTo(FollowUpModeMaster::class, 'follow_up_mode_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(SalesEstimateApprovalItem::class, 'sales_estimate_approval_id')->orderBy('sequence_no');
    }
}
