<?php

namespace App\Modules\SurveyorInspection\Models;

use App\Concerns\Auditable;
use App\Concerns\Searchable;
use App\Modules\ClaimIntimation\Models\ClaimIntimation;
use App\Modules\CustomerMaster\Models\CustomerMaster;
use App\Modules\CustomerVehicleMaster\Models\CustomerVehicleMaster;
use App\Modules\InsuranceCompanyMaster\Models\InsuranceCompanyMaster;
use App\Modules\JobCard\Models\JobCard;
use App\Modules\SalesEstimate\Models\SalesEstimate;
use App\Modules\SurveyorInspection\Database\Factories\SurveyorInspectionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SurveyorInspection extends Model
{
    use Auditable;
    use HasFactory;
    use Searchable;

    public const STATUS_PENDING = 'pending';

    public const STATUS_IN_PROGRESS = 'in_progress';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_CANCELLED = 'cancelled';

    protected $table = 'surveyor_inspections';

    protected $guarded = [];

    protected $casts = [
        'surveyed_at' => 'datetime',
    ];

    protected static array $searchableFields = ['inspection_no', 'surveyor_name', 'notes'];

    protected static function newFactory(): SurveyorInspectionFactory
    {
        return SurveyorInspectionFactory::new();
    }

    protected static function booted(): void
    {
        static::created(function (self $row) {
            if ($row->inspection_no === null) {
                $row->forceFill([
                    'inspection_no' => 'SI-'.str_pad((string) $row->id, 5, '0', STR_PAD_LEFT),
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
            self::STATUS_COMPLETED => 'Completed',
            self::STATUS_CANCELLED => 'Cancelled',
        ];
    }

    /** @return array<string, string> */
    public static function surveyTypes(): array
    {
        return [
            'preliminary' => 'Preliminary Survey',
            're_inspection' => 'Re-inspection',
            'final' => 'Final Survey',
            'spot' => 'Spot Survey',
            'supplementary' => 'Supplementary Survey',
        ];
    }

    /** @return array<string, string> */
    public static function approvalOutcomes(): array
    {
        return [
            'repair' => 'Repair Approved',
            'replace' => 'Replace Approved',
            'not_approved' => 'Not Approved',
            'partial' => 'Partially Approved',
            'total_loss' => 'Total Loss Approved',
        ];
    }

    /** @return array<string, string> */
    public static function notCoveredReasons(): array
    {
        return [
            'old_damage' => 'Old Damage',
            'damage_mismatch' => 'Damage Mismatch',
            'not_in_policy' => 'Not Covered in Policy',
        ];
    }

    /** @return array<string, string> */
    public static function rejectionReasons(): array
    {
        return [
            'document_missing' => 'Document Missing',
            'policy_expired' => 'Policy Expired',
            'policy_invalid' => 'Policy Not Valid',
            'fraud' => 'Fraud Suspected',
            'insufficient_evidence' => 'Insufficient Evidence',
        ];
    }

    public function jobCard(): BelongsTo
    {
        return $this->belongsTo(JobCard::class, 'job_card_id');
    }

    public function claimIntimation(): BelongsTo
    {
        return $this->belongsTo(ClaimIntimation::class, 'claim_intimation_id');
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

    public function insuranceCompany(): BelongsTo
    {
        return $this->belongsTo(InsuranceCompanyMaster::class, 'insurance_company_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(SurveyorInspectionItem::class, 'surveyor_inspection_id')->orderBy('sequence_no');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(SurveyorInspectionAttachment::class, 'surveyor_inspection_id')->orderBy('sequence_no');
    }
}
