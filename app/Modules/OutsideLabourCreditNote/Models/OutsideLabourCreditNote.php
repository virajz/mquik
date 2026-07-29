<?php

namespace App\Modules\OutsideLabourCreditNote\Models;

use App\Concerns\Auditable;
use App\Concerns\Searchable;
use App\Modules\CustomerVehicleMaster\Models\CustomerVehicleMaster;
use App\Modules\EmployeeMaster\Models\EmployeeMaster;
use App\Modules\JobCard\Models\JobCard;
use App\Modules\OutsideLabourCreditNote\Database\Factories\OutsideLabourCreditNoteFactory;
use App\Modules\OutsideLabourReturn\Models\OutsideLabourReturn;
use App\Modules\ServiceSpecialistMaster\Models\ServiceSpecialistMaster;
use App\Modules\VendorMaster\Models\VendorMaster;
use App\Modules\WorkshopDepartmentMaster\Models\WorkshopDepartmentMaster;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OutsideLabourCreditNote extends Model
{
    use Auditable;
    use HasFactory;
    use Searchable;

    public const STATUS_POSTED = 'posted';

    public const STATUS_CANCELLED = 'cancelled';

    protected $table = 'outside_labour_credit_notes';

    protected $guarded = [];

    protected $casts = ['amount' => 'decimal:2'];

    protected static array $searchableFields = ['note_no', 'notes'];

    protected static function newFactory(): OutsideLabourCreditNoteFactory
    {
        return OutsideLabourCreditNoteFactory::new();
    }

    protected static function booted(): void
    {
        static::created(function (self $row) {
            if ($row->note_no === null) {
                $prefix = $row->note_type === 'debit_note' ? 'OLDN-' : 'OLCN-';
                $row->forceFill(['note_no' => $prefix.str_pad((string) $row->id, 5, '0', STR_PAD_LEFT)])->saveQuietly();
            }
        });
    }

    /** @return array<string, string> */
    public static function noteTypes(): array
    {
        return ['credit_note' => 'Credit Note', 'debit_note' => 'Debit Note'];
    }

    /** @return array<string, string> */
    public static function invoiceTypes(): array
    {
        return [
            'e_credit_note' => 'E-Credit Note',
            'tax_credit_note' => 'Tax Credit Note',
            'bill_of_supply' => 'Bill of Supply',
            'bill_book_memo' => 'Bill Book Memo',
        ];
    }

    /** @return array<string, string> */
    public static function statuses(): array
    {
        return [self::STATUS_POSTED => 'Posted', self::STATUS_CANCELLED => 'Cancelled'];
    }

    /** Both labour-side and parts-side reasons in one list. @return array<string, string> */
    public static function returnReasons(): array
    {
        return [
            'workmanship_failure' => 'Workmanship Failure',
            'incomplete_work' => 'Incomplete Work',
            'wrong_repair' => 'Wrong Repair',
            'material_failure' => 'Material Failure',
            'quality_standard_failure' => 'Quality Standard Failure',
            'incorrect_qty' => 'Incorrect Qty',
            'incorrect_rate' => 'Incorrect Rate',
            'incorrect_discount' => 'Incorrect Discount',
            'duplicate_billing' => 'Duplicate Billing',
            'unauthorized_work' => 'Unauthorized Work',
            'defective_part' => 'Defective Part',
            'wrong_part' => 'Wrong Part',
            'damaged_part' => 'Damaged Part',
            'manufacturing_defect' => 'Manufacturing Defect',
            'premature_failure' => 'Premature Failure',
            'functional_failure' => 'Functional Failure',
            'incorrect_performance' => 'Incorrect Performance',
            'physical_damage_supply' => 'Physical Damage During Supply',
        ];
    }

    /** @return array<string, string> */
    public static function commercialSettlements(): array
    {
        return ['partial' => 'Partial Settlement', 'full' => 'Full Settlement'];
    }

    /** @return array<string, string> */
    public static function warrantyTypes(): array
    {
        return ['vendor' => 'Vendor Warranty', 'manufacturer' => 'Manufacturer Warranty'];
    }

    /** @return array<string, string> */
    public static function warrantyPeriods(): array
    {
        return ['3_months' => '3 Months', '6_months' => '6 Months', '12_months' => '12 Months', '24_months' => '24 Months'];
    }

    /** @return array<string, string> */
    public static function vendorRatingTypes(): array
    {
        return [
            'quality' => 'Quality Product',
            'price' => 'Reasonable Price',
            'delivery' => 'Ontime Delivery',
            'support' => 'Handhold Support',
        ];
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(VendorMaster::class, 'vendor_id');
    }

    public function transportCompany(): BelongsTo
    {
        return $this->belongsTo(VendorMaster::class, 'transport_company_id');
    }

    public function serviceCategory(): BelongsTo
    {
        return $this->belongsTo(ServiceSpecialistMaster::class, 'service_specialist_id');
    }

    public function return(): BelongsTo
    {
        return $this->belongsTo(OutsideLabourReturn::class, 'outside_labour_return_id');
    }

    public function jobCard(): BelongsTo
    {
        return $this->belongsTo(JobCard::class, 'job_card_id');
    }

    public function customerVehicle(): BelongsTo
    {
        return $this->belongsTo(CustomerVehicleMaster::class, 'customer_vehicle_id');
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(WorkshopDepartmentMaster::class, 'workshop_department_id');
    }

    public function advisor(): BelongsTo
    {
        return $this->belongsTo(EmployeeMaster::class, 'advisor_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(OutsideLabourCreditNoteItem::class, 'outside_labour_credit_note_id')->orderBy('sequence_no');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(OutsideLabourCreditNoteAttachment::class, 'outside_labour_credit_note_id')->orderBy('sequence_no');
    }
}
