<?php

namespace App\Modules\RegularReceipt\Models;

use App\Concerns\Auditable;
use App\Concerns\Searchable;
use App\Modules\BankMaster\Models\BankMaster;
use App\Modules\ChequeBounceReasonMaster\Models\ChequeBounceReasonMaster;
use App\Modules\CounterSalesInvoice\Models\CounterSalesInvoice;
use App\Modules\CustomerMaster\Models\CustomerMaster;
use App\Modules\EmployeeMaster\Models\EmployeeMaster;
use App\Modules\InsuranceCompanyMaster\Models\InsuranceCompanyMaster;
use App\Modules\PaymentModeMaster\Models\PaymentModeMaster;
use App\Modules\ReceiptCancellationReasonMaster\Models\ReceiptCancellationReasonMaster;
use App\Modules\ReceiptDifferenceReasonMaster\Models\ReceiptDifferenceReasonMaster;
use App\Modules\RegularReceipt\Database\Factories\RegularReceiptFactory;
use App\Modules\RegularSalesInvoice\Models\RegularSalesInvoice;
use App\Support\FinancialYear;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RegularReceipt extends Model
{
    use Auditable;
    use HasFactory;
    use Searchable;

    protected $table = 'regular_receipts';

    protected $guarded = [];

    protected $casts = [
        'amount' => 'decimal:2',
        'difference_amount' => 'decimal:2',
        'cheque_date' => 'date',
        'received_at' => 'datetime',
        'cleared_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    protected static array $searchableFields = ['receipt_no', 'reference_no', 'cheque_no', 'notes'];

    protected static function newFactory(): RegularReceiptFactory
    {
        return RegularReceiptFactory::new();
    }

    protected static function booted(): void
    {
        static::created(function (self $row) {
            if ($row->receipt_no === null) {
                $fy = FinancialYear::label($row->created_at);
                $seq = static::where('fy_label', $fy)->count() + 1;
                $row->forceFill([
                    'fy_label' => $fy,
                    'receipt_no' => 'MQ/RR/'.$fy.'/'.str_pad((string) $seq, 5, '0', STR_PAD_LEFT),
                ])->saveQuietly();
            }
        });
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(CustomerMaster::class, 'customer_id');
    }

    public function insuranceCompany(): BelongsTo
    {
        return $this->belongsTo(InsuranceCompanyMaster::class, 'insurance_company_id');
    }

    public function regularSalesInvoice(): BelongsTo
    {
        return $this->belongsTo(RegularSalesInvoice::class, 'regular_sales_invoice_id');
    }

    public function counterSalesInvoice(): BelongsTo
    {
        return $this->belongsTo(CounterSalesInvoice::class, 'counter_sales_invoice_id');
    }

    public function paymentMode(): BelongsTo
    {
        return $this->belongsTo(PaymentModeMaster::class, 'payment_mode_id');
    }

    public function bank(): BelongsTo
    {
        return $this->belongsTo(BankMaster::class, 'bank_id');
    }

    public function receivedBy(): BelongsTo
    {
        return $this->belongsTo(EmployeeMaster::class, 'received_by_id');
    }

    public function advanceReceipt(): BelongsTo
    {
        return $this->belongsTo(self::class, 'advance_receipt_id');
    }

    public function differenceReason(): BelongsTo
    {
        return $this->belongsTo(ReceiptDifferenceReasonMaster::class, 'receipt_difference_reason_id');
    }

    public function chequeBounceReason(): BelongsTo
    {
        return $this->belongsTo(ChequeBounceReasonMaster::class, 'cheque_bounce_reason_id');
    }

    public function cancellationReason(): BelongsTo
    {
        return $this->belongsTo(ReceiptCancellationReasonMaster::class, 'cancellation_reason_id');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(RegularReceiptAttachment::class, 'regular_receipt_id');
    }

    /** @return array<string, string> */
    public static function statuses(): array
    {
        return [
            'draft' => 'Draft',
            'confirmed' => 'Confirmed',
            'cancelled' => 'Cancelled',
        ];
    }

    /** @return array<string, string> */
    public static function chequeStatuses(): array
    {
        return [
            'received' => 'Received',
            'deposited' => 'Deposited',
            'cleared' => 'Cleared',
            'returned' => 'Returned',
            'cancelled' => 'Cancelled',
        ];
    }

    /** @return array<string, string> */
    public static function attachmentTypes(): array
    {
        return [
            'cheque_copy' => 'Cheque Copy',
            'utr_screenshot' => 'UTR Screenshot',
            'deposit_slip' => 'Deposit Slip',
            'payment_advice' => 'Payment Advice',
        ];
    }
}
