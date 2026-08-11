<?php

namespace App\Modules\SalesEstimate\Models;

use App\Concerns\Auditable;
use App\Concerns\Searchable;
use App\Modules\CustomerMaster\Models\CustomerMaster;
use App\Modules\CustomerVehicleMaster\Models\CustomerVehicleMaster;
use App\Modules\DamageCauseMaster\Models\DamageCauseMaster;
use App\Modules\EmployeeMaster\Models\EmployeeMaster;
use App\Modules\EstimateRevisionReasonMaster\Models\EstimateRevisionReasonMaster;
use App\Modules\EstimateTemplateMaster\Models\EstimateTemplateMaster;
use App\Modules\InsuranceCompanyMaster\Models\InsuranceCompanyMaster;
use App\Modules\JobCard\Models\JobCard;
use App\Modules\SalesEstimate\Database\Factories\SalesEstimateFactory;
use App\Modules\ServicePackageMaster\Models\ServicePackageMaster;
use App\Modules\ServiceTypeMaster\Models\ServiceTypeMaster;
use App\Modules\WorkshopDepartmentMaster\Models\WorkshopDepartmentMaster;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SalesEstimate extends Model
{
    use Auditable;
    use HasFactory;
    use Searchable;

    protected $table = 'sales_estimates';

    protected $guarded = [];

    protected $casts = [
        'discount_value' => 'decimal:2',
        'parts_total' => 'decimal:2',
        'labour_total' => 'decimal:2',
        'discount_total' => 'decimal:2',
        'tax_total' => 'decimal:2',
        'grand_total' => 'decimal:2',
        'insurance_pass_percent' => 'decimal:2',
        'prepared_at' => 'datetime',
        'sent_at' => 'datetime',
        'approved_at' => 'datetime',
    ];

    protected static array $searchableFields = ['estimate_no', 'policy_no', 'notes', 'customerVehicle.registration_no'];

    protected static function newFactory(): SalesEstimateFactory
    {
        return SalesEstimateFactory::new();
    }

    protected static function booted(): void
    {
        static::created(function (self $row) {
            if ($row->estimate_no === null) {
                $row->forceFill([
                    'estimate_no' => 'SE-'.str_pad((string) $row->id, 5, '0', STR_PAD_LEFT),
                ])->saveQuietly();
            }
        });
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(CustomerMaster::class, 'customer_id');
    }

    public function customerVehicle(): BelongsTo
    {
        return $this->belongsTo(CustomerVehicleMaster::class, 'customer_vehicle_id');
    }

    public function jobCard(): BelongsTo
    {
        return $this->belongsTo(JobCard::class, 'job_card_id');
    }

    public function damageCause(): BelongsTo
    {
        return $this->belongsTo(DamageCauseMaster::class, 'damage_cause_id');
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(WorkshopDepartmentMaster::class, 'department_id');
    }

    public function serviceType(): BelongsTo
    {
        return $this->belongsTo(ServiceTypeMaster::class, 'service_type_id');
    }

    public function advisor(): BelongsTo
    {
        return $this->belongsTo(EmployeeMaster::class, 'advisor_id');
    }

    public function technician(): BelongsTo
    {
        return $this->belongsTo(EmployeeMaster::class, 'technician_id');
    }

    public function insuranceCompany(): BelongsTo
    {
        return $this->belongsTo(InsuranceCompanyMaster::class, 'insurance_company_id');
    }

    public function servicePackage(): BelongsTo
    {
        return $this->belongsTo(ServicePackageMaster::class, 'service_package_id');
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(EstimateTemplateMaster::class, 'estimate_template_id');
    }

    public function oldEstimate(): BelongsTo
    {
        return $this->belongsTo(self::class, 'old_estimate_id');
    }

    public function revisionReason(): BelongsTo
    {
        return $this->belongsTo(EstimateRevisionReasonMaster::class, 'revision_reason_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(SalesEstimateItem::class, 'sales_estimate_id')->orderBy('sequence_no');
    }

    /**
     * @return array<string, string>
     */
    public static function estimateTypes(): array
    {
        return [
            'before' => 'Before',
            'after' => 'After',
            'additional' => 'Additional',
            'warranty' => 'Warranty',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function partsCategories(): array
    {
        return [
            'any' => 'Any',
            'genuine' => 'Genuine',
            'after_market' => 'After Market',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function statuses(): array
    {
        return [
            'pending' => 'Pending',
            'prepared' => 'Prepared',
            'sent_customer' => 'Sent to Customer',
            'sent_insurance' => 'Sent to Insurance',
            'under_approval' => 'Under Approval',
            'approved' => 'Approved',
            'partially_approved' => 'Partially Approved',
            'rejected' => 'Rejected',
            'revised' => 'Revised',
            'converted' => 'Converted to Job / Invoice',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function discountTypes(): array
    {
        return [
            'percentage' => 'Percentage (%)',
            'flat' => 'Flat Amount',
            'on_mrp' => 'On MRP (%)',
            'on_rcp' => 'On RCP / Dealer Price (%)',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function labourPriceTiers(): array
    {
        return [
            'retail' => 'Retail Price',
            'insurance_approved' => 'Insurance Approved Rate',
            'special_customer' => 'Special Customer Price',
            'bulk' => 'Bulk Price',
        ];
    }
}
