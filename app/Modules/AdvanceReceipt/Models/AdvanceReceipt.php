<?php

namespace App\Modules\AdvanceReceipt\Models;

use App\Concerns\Auditable;
use App\Concerns\Searchable;
use App\Modules\AdvanceReceipt\Database\Factories\AdvanceReceiptFactory;
use App\Modules\BankMaster\Models\BankMaster;
use App\Modules\ChequeBounceReasonMaster\Models\ChequeBounceReasonMaster;
use App\Modules\CustomerMaster\Models\CustomerMaster;
use App\Modules\CustomerVehicleMaster\Models\CustomerVehicleMaster;
use App\Modules\EmployeeMaster\Models\EmployeeMaster;
use App\Modules\InsuranceCompanyMaster\Models\InsuranceCompanyMaster;
use App\Modules\JobCard\Models\JobCard;
use App\Modules\PaymentModeMaster\Models\PaymentModeMaster;
use App\Modules\ReceiptCancellationReasonMaster\Models\ReceiptCancellationReasonMaster;
use App\Modules\ReceiptDifferenceReasonMaster\Models\ReceiptDifferenceReasonMaster;
use App\Modules\SalesEstimate\Models\SalesEstimate;
use App\Modules\ServiceTypeMaster\Models\ServiceTypeMaster;
use App\Modules\WorkshopDepartmentMaster\Models\WorkshopDepartmentMaster;
use App\Support\FinancialYear;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AdvanceReceipt extends Model
{
    use Auditable;
    use HasFactory;
    use Searchable;

    public const STATUS_PARTIALLY_RECEIVED = 'partially_received';

    public const STATUS_FULLY_RECEIVED = 'fully_received';

    public const STATUS_CANCELLED = 'cancelled';

    public const STATUS_FAILED = 'failed';

    public const STATUS_REFUNDED = 'refunded';

    protected $table = 'advance_receipts';

    protected $guarded = [];

    protected $casts = [
        'amount' => 'decimal:2',
        'difference_amount' => 'decimal:2',
        'cheque_date' => 'date',
        'received_at' => 'datetime',
    ];

    protected static array $searchableFields = ['receipt_no', 'reference_no', 'cheque_no', 'notes'];

    protected static function newFactory(): AdvanceReceiptFactory
    {
        return AdvanceReceiptFactory::new();
    }

    protected static function booted(): void
    {
        static::created(function (self $row) {
            if ($row->receipt_no === null) {
                $fy = FinancialYear::label($row->created_at);
                $seq = static::where('fy_label', $fy)->count() + 1;
                $row->forceFill([
                    'fy_label' => $fy,
                    'receipt_no' => 'MQ/AR/'.$fy.'/'.str_pad((string) $seq, 5, '0', STR_PAD_LEFT),
                ])->saveQuietly();
            }
        });
    }

    /** @return array<string, string> */
    public static function paymentStatuses(): array
    {
        return [
            self::STATUS_PARTIALLY_RECEIVED => 'Partially Received',
            self::STATUS_FULLY_RECEIVED => 'Fully Received',
            self::STATUS_CANCELLED => 'Cancelled',
            self::STATUS_FAILED => 'Failed',
            self::STATUS_REFUNDED => 'Refunded',
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

    public function jobCard(): BelongsTo
    {
        return $this->belongsTo(JobCard::class, 'job_card_id');
    }

    public function salesEstimate(): BelongsTo
    {
        return $this->belongsTo(SalesEstimate::class, 'sales_estimate_id');
    }

    public function insuranceCompany(): BelongsTo
    {
        return $this->belongsTo(InsuranceCompanyMaster::class, 'insurance_company_id');
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

    public function serviceType(): BelongsTo
    {
        return $this->belongsTo(ServiceTypeMaster::class, 'service_type_id');
    }

    public function receivedBy(): BelongsTo
    {
        return $this->belongsTo(EmployeeMaster::class, 'employee_id');
    }

    public function paymentMode(): BelongsTo
    {
        return $this->belongsTo(PaymentModeMaster::class, 'payment_mode_id');
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

    public function differenceReason(): BelongsTo
    {
        return $this->belongsTo(ReceiptDifferenceReasonMaster::class, 'difference_reason_id');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(AdvanceReceiptAttachment::class, 'advance_receipt_id')->orderBy('sequence_no');
    }
}
