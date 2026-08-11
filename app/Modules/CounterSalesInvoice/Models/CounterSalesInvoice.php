<?php

namespace App\Modules\CounterSalesInvoice\Models;

use App\Concerns\Auditable;
use App\Concerns\Searchable;
use App\Modules\CounterSalesInvoice\Database\Factories\CounterSalesInvoiceFactory;
use App\Modules\CourierCompanyMaster\Models\CourierCompanyMaster;
use App\Modules\CustomerMaster\Models\CustomerMaster;
use App\Modules\EmployeeMaster\Models\EmployeeMaster;
use App\Modules\InvoiceCancellationReasonMaster\Models\InvoiceCancellationReasonMaster;
use App\Modules\LossTypeMaster\Models\LossTypeMaster;
use App\Modules\PaymentModeMaster\Models\PaymentModeMaster;
use App\Modules\TransportModeMaster\Models\TransportModeMaster;
use App\Modules\VendorMaster\Models\VendorMaster;
use App\Modules\WorkshopDepartmentMaster\Models\WorkshopDepartmentMaster;
use App\Support\FinancialYear;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CounterSalesInvoice extends Model
{
    use Auditable;
    use HasFactory;
    use Searchable;

    protected $table = 'counter_sales_invoices';

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
        'amount_paid' => 'decimal:2',
        'balance_due' => 'decimal:2',
        'invoiced_at' => 'datetime',
        'paid_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    protected static array $searchableFields = ['invoice_no', 'tracking_no', 'notes', 'customer.first_name', 'customer.last_name'];

    protected static function newFactory(): CounterSalesInvoiceFactory
    {
        return CounterSalesInvoiceFactory::new();
    }

    protected static function booted(): void
    {
        static::created(function (self $row) {
            if ($row->invoice_no === null) {
                $fy = FinancialYear::label($row->created_at);
                $seq = static::where('fy_label', $fy)->count() + 1;
                $row->forceFill([
                    'fy_label' => $fy,
                    'invoice_no' => 'MQ/CS/'.$fy.'/'.str_pad((string) $seq, 5, '0', STR_PAD_LEFT),
                ])->saveQuietly();
            }
        });
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(CustomerMaster::class, 'customer_id');
    }

    public function courierCompany(): BelongsTo
    {
        return $this->belongsTo(CourierCompanyMaster::class, 'courier_company_id');
    }

    public function transportMode(): BelongsTo
    {
        return $this->belongsTo(TransportModeMaster::class, 'transport_mode_id');
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(WorkshopDepartmentMaster::class, 'department_id');
    }

    public function lossType(): BelongsTo
    {
        return $this->belongsTo(LossTypeMaster::class, 'loss_type_id');
    }

    public function paymentMode(): BelongsTo
    {
        return $this->belongsTo(PaymentModeMaster::class, 'payment_mode_id');
    }

    public function cancellationReason(): BelongsTo
    {
        return $this->belongsTo(InvoiceCancellationReasonMaster::class, 'cancellation_reason_id');
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

    public function items(): HasMany
    {
        return $this->hasMany(CounterSalesInvoiceItem::class, 'counter_sales_invoice_id')->orderBy('sequence_no');
    }

    /** @return array<string, string> */
    public static function statuses(): array
    {
        return [
            'draft' => 'Draft',
            'finalized' => 'Finalized',
            'cancelled' => 'Cancelled',
            'credit_note' => 'Credit Note (CN)',
        ];
    }

    /** @return array<string, string> */
    public static function paymentStatuses(): array
    {
        return [
            'unpaid' => 'Unpaid',
            'partially_paid' => 'Partially Paid',
            'fully_paid' => 'Fully Paid',
            'refunded' => 'Refunded',
        ];
    }

    /** @return array<string, string> */
    public static function deliveryTypes(): array
    {
        return [
            'counter_pickup' => 'Counter Pickup',
            'home_delivery' => 'Home Delivery',
            'courier' => 'Courier',
            'porter' => 'Porter',
            'liftor' => 'Liftor',
        ];
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
}
