<?php

namespace App\Modules\PurchaseEntry\Models;

use App\Concerns\Auditable;
use App\Concerns\Searchable;
use App\Modules\ChallanEntry\Models\Challan;
use App\Modules\ChallanReasonMaster\Models\ChallanReasonMaster;
use App\Modules\CourierCompanyMaster\Models\CourierCompanyMaster;
use App\Modules\EmployeeMaster\Models\EmployeeMaster;
use App\Modules\JobCard\Models\JobCard;
use App\Modules\PurchaseEntry\Database\Factories\PurchaseEntryFactory;
use App\Modules\TransportModeMaster\Models\TransportModeMaster;
use App\Modules\VendorMaster\Models\VendorMaster;
use App\Modules\WorkshopDepartmentMaster\Models\WorkshopDepartmentMaster;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PurchaseEntry extends Model
{
    use Auditable;
    use HasFactory;
    use Searchable;

    protected $table = 'purchase_entries';

    protected $guarded = [];

    protected $casts = [
        'invoice_date' => 'date',
        'parts_total' => 'decimal:2',
        'discount_total' => 'decimal:2',
        'tax_total' => 'decimal:2',
        'charges_total' => 'decimal:2',
        'grand_total' => 'decimal:2',
    ];

    protected static array $searchableFields = ['purchase_no', 'invoice_no', 'po_reference', 'notes'];

    protected static function newFactory(): PurchaseEntryFactory
    {
        return PurchaseEntryFactory::new();
    }

    protected static function booted(): void
    {
        static::created(function (self $row) {
            if ($row->purchase_no === null) {
                $row->forceFill([
                    'purchase_no' => 'PE-'.str_pad((string) $row->id, 5, '0', STR_PAD_LEFT),
                ])->saveQuietly();
            }
        });
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(VendorMaster::class, 'vendor_id');
    }

    public function challan(): BelongsTo
    {
        return $this->belongsTo(Challan::class, 'challan_id');
    }

    public function transportMode(): BelongsTo
    {
        return $this->belongsTo(TransportModeMaster::class, 'transport_mode_id');
    }

    public function transportCompany(): BelongsTo
    {
        return $this->belongsTo(CourierCompanyMaster::class, 'transport_company_id');
    }

    public function challanReason(): BelongsTo
    {
        return $this->belongsTo(ChallanReasonMaster::class, 'challan_reason_id');
    }

    public function jobCard(): BelongsTo
    {
        return $this->belongsTo(JobCard::class, 'job_card_id');
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(WorkshopDepartmentMaster::class, 'department_id');
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

    public function driver(): BelongsTo
    {
        return $this->belongsTo(EmployeeMaster::class, 'driver_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseEntryItem::class, 'purchase_entry_id')->orderBy('sequence_no');
    }

    public function charges(): HasMany
    {
        return $this->hasMany(PurchaseEntryCharge::class, 'purchase_entry_id')->orderBy('sequence_no');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(PurchaseEntryAttachment::class, 'purchase_entry_id');
    }

    /** @return array<string, string> */
    public static function invoiceTypes(): array
    {
        return [
            'tax_invoice' => 'Tax Invoice',
            'e_invoice' => 'E-Invoice',
            'retail_invoice' => 'Retail Invoice',
            'bill_of_supply' => 'Bill of Supply',
        ];
    }

    /** @return array<string, string> */
    public static function purchaseTypes(): array
    {
        return [
            'stock' => 'Stock Purchase',
            'direct_job_card' => 'Direct Job Card Purchase',
            'outside_labour' => 'Outside Labour Purchase',
            'emergency' => 'Emergency Purchase',
        ];
    }

    /** @return array<string, string> */
    public static function discountSchemes(): array
    {
        return [
            'line' => 'Line Discount',
            'bill' => 'Bill Discount',
            'cash' => 'Cash Discount',
            'scheme' => 'Scheme Discount',
        ];
    }

    /** @return array<string, string> */
    public static function inventoryStatuses(): array
    {
        return Challan::inventoryStatuses();
    }

    /** @return array<string, string> */
    public static function materialConditions(): array
    {
        return Challan::materialConditions();
    }

    /** @return array<string, string> */
    public static function invoiceStatuses(): array
    {
        return Challan::invoiceStatuses();
    }
}
