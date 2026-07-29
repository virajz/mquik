<?php

namespace App\Modules\InvoiceCorrection\Models;

use App\Concerns\Auditable;
use App\Concerns\Searchable;
use App\Modules\CustomerMaster\Models\CustomerMaster;
use App\Modules\CustomerVehicleMaster\Models\CustomerVehicleMaster;
use App\Modules\EmployeeMaster\Models\EmployeeMaster;
use App\Modules\InsuranceCompanyMaster\Models\InsuranceCompanyMaster;
use App\Modules\InvoiceCorrection\Database\Factories\InvoiceCorrectionFactory;
use App\Modules\JobCard\Models\JobCard;
use App\Modules\ServiceTypeMaster\Models\ServiceTypeMaster;
use App\Modules\WorkshopDepartmentMaster\Models\WorkshopDepartmentMaster;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InvoiceCorrection extends Model
{
    use Auditable;
    use HasFactory;
    use Searchable;

    public const STATUS_REQUESTED = 'requested';

    public const STATUS_UNDER_REVIEW = 'under_review';

    public const STATUS_ON_HOLD = 'on_hold';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_CORRECTED = 'corrected';

    public const STATUS_REJECTED = 'rejected';

    protected $table = 'invoice_corrections';

    protected $guarded = [];

    protected $casts = [
        'requested_at' => 'datetime',
        'approved_at' => 'datetime',
        'corrected_at' => 'datetime',
    ];

    protected static array $searchableFields = ['correction_no', 'invoice_reference', 'notes'];

    protected static function newFactory(): InvoiceCorrectionFactory
    {
        return InvoiceCorrectionFactory::new();
    }

    protected static function booted(): void
    {
        static::created(function (self $row) {
            if ($row->correction_no === null) {
                $row->forceFill(['correction_no' => 'INC-'.str_pad((string) $row->id, 5, '0', STR_PAD_LEFT)])->saveQuietly();
            }
        });
    }

    /** @return array<string, string> */
    public static function statuses(): array
    {
        return [
            self::STATUS_REQUESTED => 'Requested',
            self::STATUS_UNDER_REVIEW => 'Under Review',
            self::STATUS_ON_HOLD => 'On Hold',
            self::STATUS_APPROVED => 'Approved',
            self::STATUS_CORRECTED => 'Corrected',
            self::STATUS_REJECTED => 'Rejected',
        ];
    }

    /** @return array<string, string> */
    public static function requestTypes(): array
    {
        return [
            'name_b2c_b2c' => 'Billing Name — B2C to B2C',
            'name_b2c_b2b' => 'Billing Name — B2C to B2B',
            'name_b2b_b2c' => 'Billing Name — B2B to B2C',
            'customer_gst' => 'Customer GST No. Correction',
            'insurance_gst' => 'Ins. Company GST No. Correction',
            'address' => 'Address Correction',
            'vehicle_no' => 'Vehicle No. Correction',
            'vehicle_name' => 'Vehicle Name Correction',
            'labour_qty' => 'Labour Qty Correction',
            'spares_qty' => 'Spares Qty Correction',
            'labour_rate' => 'Labour Rate Correction',
            'spares_rate' => 'Spares Rate Correction',
            'new_labour' => 'New Labour Addition',
            'new_spares' => 'New Spares Addition',
            'delete_labour' => 'Wrong Labour Deletion',
            'delete_spares' => 'Wrong Spares Deletion',
            'discount' => 'Discount Correction',
            'tax_rate' => 'Tax Rate Correction',
            'insurance_share' => 'Insurance Share Correction',
            'customer_recommendation' => 'Customer Recommendation Correction',
        ];
    }

    /** @return array<string, string> */
    public static function correctionReasons(): array
    {
        return [
            'data_entry_mistake' => 'Data Entry Mistake',
            'wrong_customer' => 'Wrong Customer Details',
            'wrong_vehicle' => 'Wrong Vehicle Details',
            'wrong_gst' => 'Wrong GST Number',
            'wrong_tax' => 'Wrong Tax Calculation',
            'wrong_labour_rate' => 'Wrong Labour Rate',
            'wrong_parts_rate' => 'Wrong Parts Rate',
            'customer_request' => 'Customer Request',
            'management_instruction' => 'Management Instruction',
            'system_error' => 'System Error',
            'other' => 'Other',
        ];
    }

    /** @return array<string, string> */
    public static function priorities(): array
    {
        return ['normal' => 'Normal', 'high' => 'High', 'urgent' => 'Urgent'];
    }

    /** @return array<string, string> */
    public static function billingActions(): array
    {
        return [
            'correct_existing' => 'Correct Existing Invoice',
            'credit_note_reissue' => 'Issue Credit Note & Reissue',
            'cancel_reissue' => 'Cancel & Reissue Correct Invoice',
        ];
    }

    /** @return array<string, string> */
    public static function invoiceTypes(): array
    {
        return ['regular' => 'Regular', 'insurance' => 'Insurance', 'counter_sales' => 'Counter Sales', 'qcare' => 'Qcare'];
    }

    /** @return array<string, string> */
    public static function rejectionReasons(): array
    {
        return ['verbal_clarification' => 'Verbal Clarification', 'management_decision' => 'Management Decision', 'other' => 'Other'];
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

    public function insuranceCompany(): BelongsTo
    {
        return $this->belongsTo(InsuranceCompanyMaster::class, 'insurance_company_id');
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(WorkshopDepartmentMaster::class, 'workshop_department_id');
    }

    public function serviceType(): BelongsTo
    {
        return $this->belongsTo(ServiceTypeMaster::class, 'service_type_id');
    }

    public function advisor(): BelongsTo
    {
        return $this->belongsTo(EmployeeMaster::class, 'advisor_id');
    }

    public function mistakeBy(): BelongsTo
    {
        return $this->belongsTo(EmployeeMaster::class, 'mistake_by_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(InvoiceCorrectionItem::class, 'invoice_correction_id')->orderBy('sequence_no');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(InvoiceCorrectionAttachment::class, 'invoice_correction_id')->orderBy('sequence_no');
    }
}
