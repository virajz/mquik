<?php

namespace App\Modules\ReceiptRefund\Models;

use App\Concerns\Auditable;
use App\Concerns\Searchable;
use App\Modules\BankMaster\Models\BankMaster;
use App\Modules\ChequeBounceReasonMaster\Models\ChequeBounceReasonMaster;
use App\Modules\CustomerMaster\Models\CustomerMaster;
use App\Modules\CustomerVehicleMaster\Models\CustomerVehicleMaster;
use App\Modules\EmployeeMaster\Models\EmployeeMaster;
use App\Modules\InsuranceCompanyMaster\Models\InsuranceCompanyMaster;
use App\Modules\JobCard\Models\JobCard;
use App\Modules\PaymentModeMaster\Models\PaymentModeMaster;
use App\Modules\ReceiptCancellationReasonMaster\Models\ReceiptCancellationReasonMaster;
use App\Modules\ReceiptRefund\Database\Factories\ReceiptRefundFactory;
use App\Modules\RefundTypeMaster\Models\RefundTypeMaster;
use App\Modules\RegularReceipt\Models\RegularReceipt;
use App\Modules\RegularSalesInvoice\Models\RegularSalesInvoice;
use App\Modules\SalesEstimate\Models\SalesEstimate;
use App\Modules\SalesReturn\Models\SalesReturn;
use App\Modules\ServiceTypeMaster\Models\ServiceTypeMaster;
use App\Modules\WorkshopDepartmentMaster\Models\WorkshopDepartmentMaster;
use App\Support\FinancialYear;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ReceiptRefund extends Model
{
    use Auditable;
    use HasFactory;
    use Searchable;

    protected $table = 'receipt_refunds';

    protected $guarded = [];

    protected $casts = [
        'amount' => 'decimal:2',
        'cheque_date' => 'date',
        'refunded_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    protected static array $searchableFields = ['refund_no', 'reference_no', 'cheque_no', 'notes'];

    protected static function newFactory(): ReceiptRefundFactory
    {
        return ReceiptRefundFactory::new();
    }

    protected static function booted(): void
    {
        static::created(function (self $row) {
            if ($row->refund_no === null) {
                $fy = FinancialYear::label($row->created_at);
                $seq = static::where('fy_label', $fy)->count() + 1;
                $row->forceFill([
                    'fy_label' => $fy,
                    'refund_no' => 'MQ/RF/'.$fy.'/'.str_pad((string) $seq, 5, '0', STR_PAD_LEFT),
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

    public function insuranceCompany(): BelongsTo
    {
        return $this->belongsTo(InsuranceCompanyMaster::class, 'insurance_company_id');
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(WorkshopDepartmentMaster::class, 'department_id');
    }

    public function serviceType(): BelongsTo
    {
        return $this->belongsTo(ServiceTypeMaster::class, 'service_type_id');
    }

    public function refundType(): BelongsTo
    {
        return $this->belongsTo(RefundTypeMaster::class, 'refund_type_id');
    }

    public function advanceReceipt(): BelongsTo
    {
        return $this->belongsTo(RegularReceipt::class, 'advance_receipt_id');
    }

    public function regularReceipt(): BelongsTo
    {
        return $this->belongsTo(RegularReceipt::class, 'regular_receipt_id');
    }

    public function jobCard(): BelongsTo
    {
        return $this->belongsTo(JobCard::class, 'job_card_id');
    }

    public function salesEstimate(): BelongsTo
    {
        return $this->belongsTo(SalesEstimate::class, 'sales_estimate_id');
    }

    public function regularSalesInvoice(): BelongsTo
    {
        return $this->belongsTo(RegularSalesInvoice::class, 'regular_sales_invoice_id');
    }

    public function salesReturn(): BelongsTo
    {
        return $this->belongsTo(SalesReturn::class, 'sales_return_id');
    }

    public function advisor(): BelongsTo
    {
        return $this->belongsTo(EmployeeMaster::class, 'advisor_id');
    }

    public function refundedBy(): BelongsTo
    {
        return $this->belongsTo(EmployeeMaster::class, 'refunded_by_id');
    }

    public function refundMode(): BelongsTo
    {
        return $this->belongsTo(PaymentModeMaster::class, 'refund_mode_id');
    }

    public function bank(): BelongsTo
    {
        return $this->belongsTo(BankMaster::class, 'bank_id');
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
        return $this->hasMany(ReceiptRefundAttachment::class, 'receipt_refund_id');
    }

    /** @return array<string, string> */
    public static function refundAgainstOptions(): array
    {
        return [
            'advance_receipt' => 'Advance Receipt',
            'regular_receipt' => 'Regular Receipt',
        ];
    }

    /** @return array<string, string> */
    public static function refundStatuses(): array
    {
        return [
            'requested' => 'Requested',
            'on_hold' => 'On Hold',
            'refunded' => 'Refunded',
            'rejected' => 'Rejected',
            'cancelled' => 'Cancelled',
        ];
    }

    /** @return array<string, string> */
    public static function chequeStatuses(): array
    {
        return [
            'issued' => 'Issued',
            'cleared' => 'Cleared',
            'bounced' => 'Bounced',
            'cancelled' => 'Cancelled',
        ];
    }

    /** @return array<string, string> */
    public static function attachmentTypes(): array
    {
        return [
            'customer_request_proof' => 'Customer Request Proof',
            'cheque_copy' => 'Cheque Copy',
            'utr_screenshot' => 'UTR Screenshot',
            'payment_advice' => 'Payment Advice',
        ];
    }
}
