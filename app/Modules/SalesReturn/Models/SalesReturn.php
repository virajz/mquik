<?php

namespace App\Modules\SalesReturn\Models;

use App\Concerns\Auditable;
use App\Concerns\Searchable;
use App\Modules\CounterSalesInvoice\Models\CounterSalesInvoice;
use App\Modules\CustomerMaster\Models\CustomerMaster;
use App\Modules\CustomerVehicleMaster\Models\CustomerVehicleMaster;
use App\Modules\EmployeeMaster\Models\EmployeeMaster;
use App\Modules\InsuranceCompanyMaster\Models\InsuranceCompanyMaster;
use App\Modules\InvoiceCancellationReasonMaster\Models\InvoiceCancellationReasonMaster;
use App\Modules\RegularSalesInvoice\Models\RegularSalesInvoice;
use App\Modules\SalesReturn\Database\Factories\SalesReturnFactory;
use App\Modules\SalesReturnReasonMaster\Models\SalesReturnReasonMaster;
use App\Modules\ServicePackageMaster\Models\ServicePackageMaster;
use App\Modules\VendorMaster\Models\VendorMaster;
use App\Modules\WorkshopDepartmentMaster\Models\WorkshopDepartmentMaster;
use App\Support\FinancialYear;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SalesReturn extends Model
{
    use Auditable;
    use HasFactory;
    use Searchable;

    protected $table = 'sales_returns';

    protected $guarded = [];

    protected $casts = [
        'discount_value' => 'decimal:2',
        'parts_total' => 'decimal:2',
        'labour_total' => 'decimal:2',
        'discount_total' => 'decimal:2',
        'tax_total' => 'decimal:2',
        'grand_total' => 'decimal:2',
        'refunded_amount' => 'decimal:2',
        'balance_refund' => 'decimal:2',
        'returned_at' => 'datetime',
        'refunded_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    protected static array $searchableFields = ['return_no', 'notes'];

    protected static function newFactory(): SalesReturnFactory
    {
        return SalesReturnFactory::new();
    }

    protected static function booted(): void
    {
        static::created(function (self $row) {
            if ($row->return_no === null) {
                $fy = FinancialYear::label($row->created_at);
                $prefix = self::seriesPrefixes()[$row->return_type] ?? 'SR';
                $seq = static::where('return_type', $row->return_type)->where('fy_label', $fy)->count() + 1;
                $row->forceFill([
                    'fy_label' => $fy,
                    'return_no' => 'MQ/'.$prefix.'/'.$fy.'/'.str_pad((string) $seq, 5, '0', STR_PAD_LEFT),
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

    public function regularSalesInvoice(): BelongsTo
    {
        return $this->belongsTo(RegularSalesInvoice::class, 'regular_sales_invoice_id');
    }

    public function counterSalesInvoice(): BelongsTo
    {
        return $this->belongsTo(CounterSalesInvoice::class, 'counter_sales_invoice_id');
    }

    public function insuranceCompany(): BelongsTo
    {
        return $this->belongsTo(InsuranceCompanyMaster::class, 'insurance_company_id');
    }

    public function servicePackage(): BelongsTo
    {
        return $this->belongsTo(ServicePackageMaster::class, 'service_package_id');
    }

    public function amcPackage(): BelongsTo
    {
        return $this->belongsTo(ServicePackageMaster::class, 'amc_package_id');
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

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(VendorMaster::class, 'vendor_id');
    }

    public function returnReason(): BelongsTo
    {
        return $this->belongsTo(SalesReturnReasonMaster::class, 'sales_return_reason_id');
    }

    public function cancellationReason(): BelongsTo
    {
        return $this->belongsTo(InvoiceCancellationReasonMaster::class, 'cancellation_reason_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(SalesReturnItem::class, 'sales_return_id')->orderBy('sequence_no');
    }

    /** @return array<string, string> */
    public static function returnTypes(): array
    {
        return [
            'regular' => 'Regular',
            'insurance' => 'Insurance',
            'counter' => 'Counter',
        ];
    }

    /** Series prefix per return type: SR (regular) / IR (insurance) / CR (counter). */
    public static function seriesPrefixes(): array
    {
        return [
            'regular' => 'SR',
            'insurance' => 'IR',
            'counter' => 'CR',
        ];
    }

    /** @return array<string, string> */
    public static function referenceModes(): array
    {
        return [
            'whole_bill' => 'Whole Bill',
            'item_wise' => 'Item Wise',
        ];
    }

    /** @return array<string, string> */
    public static function statuses(): array
    {
        return [
            'draft' => 'Draft',
            'finalized' => 'Finalized',
            'cancelled' => 'Cancelled',
        ];
    }

    /** @return array<string, string> */
    public static function refundStatuses(): array
    {
        return [
            'unpaid' => 'Unpaid',
            'partially_refunded' => 'Partially Refunded',
            'fully_refunded' => 'Fully Refunded',
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
}
