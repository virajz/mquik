<?php

namespace App\Modules\Proforma\Models;

use App\Concerns\Auditable;
use App\Concerns\Searchable;
use App\Modules\CustomerMaster\Models\CustomerMaster;
use App\Modules\CustomerVehicleMaster\Models\CustomerVehicleMaster;
use App\Modules\EmployeeMaster\Models\EmployeeMaster;
use App\Modules\EstimateRevisionReasonMaster\Models\EstimateRevisionReasonMaster;
use App\Modules\InsuranceCompanyMaster\Models\InsuranceCompanyMaster;
use App\Modules\JobCard\Models\JobCard;
use App\Modules\LossReasonMaster\Models\LossReasonMaster;
use App\Modules\LossTypeMaster\Models\LossTypeMaster;
use App\Modules\Proforma\Database\Factories\ProformaFactory;
use App\Modules\SalesEstimate\Models\SalesEstimate;
use App\Modules\ServicePackageMaster\Models\ServicePackageMaster;
use App\Modules\ServiceTypeMaster\Models\ServiceTypeMaster;
use App\Modules\VendorMaster\Models\VendorMaster;
use App\Modules\WorkshopDepartmentMaster\Models\WorkshopDepartmentMaster;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Proforma extends Model
{
    use Auditable;
    use HasFactory;
    use Searchable;

    protected $table = 'proformas';

    protected $guarded = [];

    protected $casts = [
        'discount_value' => 'decimal:2',
        'parts_total' => 'decimal:2',
        'labour_total' => 'decimal:2',
        'discount_total' => 'decimal:2',
        'tax_total' => 'decimal:2',
        'grand_total' => 'decimal:2',
        'cost_total' => 'decimal:2',
        'profit_total' => 'decimal:2',
        'margin_percent' => 'decimal:2',
        'deductions_total' => 'decimal:2',
        'prepared_at' => 'datetime',
        'sent_at' => 'datetime',
        'approved_at' => 'datetime',
    ];

    protected static array $searchableFields = ['proforma_no', 'policy_no', 'notes', 'customerVehicle.registration_no'];

    protected static function newFactory(): ProformaFactory
    {
        return ProformaFactory::new();
    }

    protected static function booted(): void
    {
        static::created(function (self $row) {
            if ($row->proforma_no === null) {
                $row->forceFill([
                    'proforma_no' => 'PF-'.str_pad((string) $row->id, 5, '0', STR_PAD_LEFT),
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

    public function salesEstimate(): BelongsTo
    {
        return $this->belongsTo(SalesEstimate::class, 'sales_estimate_id');
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(WorkshopDepartmentMaster::class, 'department_id');
    }

    public function serviceType(): BelongsTo
    {
        return $this->belongsTo(ServiceTypeMaster::class, 'service_type_id');
    }

    public function insuranceCompany(): BelongsTo
    {
        return $this->belongsTo(InsuranceCompanyMaster::class, 'insurance_company_id');
    }

    public function servicePackage(): BelongsTo
    {
        return $this->belongsTo(ServicePackageMaster::class, 'service_package_id');
    }

    public function lossType(): BelongsTo
    {
        return $this->belongsTo(LossTypeMaster::class, 'loss_type_id');
    }

    public function lossReason(): BelongsTo
    {
        return $this->belongsTo(LossReasonMaster::class, 'loss_reason_id');
    }

    public function revisionReason(): BelongsTo
    {
        return $this->belongsTo(EstimateRevisionReasonMaster::class, 'revision_reason_id');
    }

    public function advisor(): BelongsTo
    {
        return $this->belongsTo(EmployeeMaster::class, 'advisor_id');
    }

    public function technician(): BelongsTo
    {
        return $this->belongsTo(EmployeeMaster::class, 'technician_id');
    }

    public function storeIncharge(): BelongsTo
    {
        return $this->belongsTo(EmployeeMaster::class, 'store_incharge_id');
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(VendorMaster::class, 'vendor_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(ProformaItem::class, 'proforma_id')->orderBy('sequence_no');
    }

    public function deductions(): HasMany
    {
        return $this->hasMany(ProformaDeduction::class, 'proforma_id')->orderBy('sequence_no');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(ProformaAttachment::class, 'proforma_id');
    }

    /** @return array<string, string> */
    public static function warrantyTypes(): array
    {
        return [
            'no_warranty' => 'No Warranty',
            'self' => 'Self Warranty',
            'vendor' => 'Vendor Warranty',
            'manufacturer' => 'Manufacturer Warranty',
        ];
    }

    /** @return array<string, string> */
    public static function warrantyPeriods(): array
    {
        return [
            '3m' => '3 Months',
            '6m' => '6 Months',
            '12m' => '12 Months',
            '24m' => '24 Months',
        ];
    }

    /** @return array<string, string> */
    public static function discountTypes(): array
    {
        return [
            'line' => 'Line Discount',
            'scheme' => 'Scheme Discount',
            'cash' => 'Cash Discount',
        ];
    }

    /** @return array<string, string> */
    public static function approvalAuthorities(): array
    {
        return [
            'store_executive' => 'Store Executive (Prepared By)',
            'store_incharge' => 'Store In-charge',
            'service_advisor' => 'Service Advisor',
            'workshop_manager' => 'Workshop Manager / Admin / Owner',
            'customer' => 'Customer',
            'insurance_company' => 'Insurance Company',
        ];
    }

    /** @return array<string, string> */
    public static function approvalStatuses(): array
    {
        return [
            'pending' => 'Pending',
            'approved' => 'Approved',
            'partially_approved' => 'Partially Approved',
            'rejected' => 'Rejected',
            'on_hold' => 'On Hold',
            'under_review' => 'Under Review',
        ];
    }

    /** @return array<string, string> */
    public static function communicationModes(): array
    {
        return [
            'whatsapp' => 'WhatsApp',
            'email' => 'Email',
            'physical_signature' => 'Physical Signature',
        ];
    }

    /** @return array<string, string> */
    public static function statuses(): array
    {
        return [
            'draft' => 'Draft',
            'wip' => 'WIP',
            'ready' => 'Ready',
            'sent_customer' => 'Sent to Customer',
            'sent_insurance' => 'Sent to Insurance Company',
            'under_review' => 'Under Review',
            'approved' => 'Approved',
            'rejected' => 'Rejected',
            'converted' => 'Converted to Invoice',
            'cancelled' => 'Cancelled',
        ];
    }
}
