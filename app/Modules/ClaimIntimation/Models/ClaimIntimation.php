<?php

namespace App\Modules\ClaimIntimation\Models;

use App\Concerns\Auditable;
use App\Concerns\Searchable;
use App\Modules\ClaimIntimation\Database\Factories\ClaimIntimationFactory;
use App\Modules\ClaimTypeMaster\Models\ClaimTypeMaster;
use App\Modules\CustomerMaster\Models\CustomerMaster;
use App\Modules\CustomerVehicleMaster\Models\CustomerVehicleMaster;
use App\Modules\EmployeeMaster\Models\EmployeeMaster;
use App\Modules\InsuranceCompanyMaster\Models\InsuranceCompanyMaster;
use App\Modules\InsurancePolicyTypeMaster\Models\InsurancePolicyTypeMaster;
use App\Modules\JobCard\Models\JobCard;
use App\Modules\WorkshopDepartmentMaster\Models\WorkshopDepartmentMaster;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClaimIntimation extends Model
{
    use Auditable;
    use HasFactory;
    use Searchable;

    public const STATUS_PENDING = 'pending';

    public const STATUS_INTIMATED = 'intimated';

    public const STATUS_CANCELLED = 'cancelled';

    protected $table = 'claim_intimations';

    protected $guarded = [];

    protected $casts = [
        'intimated_at' => 'datetime',
    ];

    protected static array $searchableFields = ['intimation_no', 'policy_no', 'claim_no', 'notes'];

    protected static function newFactory(): ClaimIntimationFactory
    {
        return ClaimIntimationFactory::new();
    }

    protected static function booted(): void
    {
        static::created(function (self $row) {
            if ($row->intimation_no === null) {
                $row->forceFill([
                    'intimation_no' => 'CI-'.str_pad((string) $row->id, 5, '0', STR_PAD_LEFT),
                ])->saveQuietly();
            }
        });
    }

    /** @return array<string, string> */
    public static function statuses(): array
    {
        return [
            self::STATUS_PENDING => 'Pending',
            self::STATUS_INTIMATED => 'Intimated',
            self::STATUS_CANCELLED => 'Cancelled',
        ];
    }

    /** @return array<string, string> */
    public static function damageNatures(): array
    {
        return [
            'accident' => 'Accident Claim',
            'fire' => 'Fire Damage',
            'theft' => 'Theft',
            'natural_calamity' => 'Natural Calamity',
            'other' => 'Other',
        ];
    }

    /** @return array<string, string> */
    public static function intimationModes(): array
    {
        return [
            'email' => 'Email',
            'insurance_portal' => 'Insurance Portal',
            'api' => 'API Integration',
            'phone' => 'Phone Call',
            'mobile_app' => 'Mobile App',
        ];
    }

    /** @return array<string, string> */
    public static function pendingReasons(): array
    {
        return [
            'policy_expired' => 'Policy Expired',
            'delay_in_intimation' => 'Delay in Intimation',
        ];
    }

    /** Survey turnaround the surveyor is expected to meet. @return array<string, string> */
    public static function surveyTats(): array
    {
        return [
            'within_24h' => 'Survey within 24 hrs',
            'within_48h' => 'Survey within 48 hrs',
            'within_72h' => 'Survey within 72 hrs',
        ];
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

    public function department(): BelongsTo
    {
        return $this->belongsTo(WorkshopDepartmentMaster::class, 'workshop_department_id');
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(EmployeeMaster::class, 'employee_id');
    }

    public function insuranceCompany(): BelongsTo
    {
        return $this->belongsTo(InsuranceCompanyMaster::class, 'insurance_company_id');
    }

    public function policyType(): BelongsTo
    {
        return $this->belongsTo(InsurancePolicyTypeMaster::class, 'insurance_policy_type_id');
    }

    public function claimType(): BelongsTo
    {
        return $this->belongsTo(ClaimTypeMaster::class, 'claim_type_id');
    }
}
