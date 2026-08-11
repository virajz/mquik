<?php

namespace App\Modules\AdvancePayment\Models;

use App\Concerns\Auditable;
use App\Concerns\Searchable;
use App\Modules\AdvancePayment\Database\Factories\AdvancePaymentFactory;
use App\Modules\BankMaster\Models\BankMaster;
use App\Modules\ChequeBounceReasonMaster\Models\ChequeBounceReasonMaster;
use App\Modules\CustomerMaster\Models\CustomerMaster;
use App\Modules\CustomerVehicleMaster\Models\CustomerVehicleMaster;
use App\Modules\EmployeeMaster\Models\EmployeeMaster;
use App\Modules\JobCard\Models\JobCard;
use App\Modules\PaymentModeMaster\Models\PaymentModeMaster;
use App\Modules\ServiceTypeMaster\Models\ServiceTypeMaster;
use App\Modules\VendorAdvanceRequest\Models\VendorAdvanceRequest;
use App\Modules\VendorMaster\Models\VendorMaster;
use App\Modules\VendorPurchaseInquiry\Models\VendorPurchaseInquiry;
use App\Modules\WorkshopDepartmentMaster\Models\WorkshopDepartmentMaster;
use App\Support\FinancialYear;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AdvancePayment extends Model
{
    use Auditable;
    use HasFactory;
    use Searchable;

    public const STATUS_POSTED = 'posted';

    public const STATUS_CANCELLED = 'cancelled';

    public const STATUS_REVERSED = 'reversed';

    protected $table = 'advance_payments';

    protected $guarded = [];

    protected $casts = [
        'amount' => 'decimal:2',
        'cheque_date' => 'date',
        'paid_at' => 'datetime',
    ];

    protected static array $searchableFields = ['payment_no', 'reference_no', 'cheque_no', 'notes', 'jobCard.job_card_no'];

    protected static function newFactory(): AdvancePaymentFactory
    {
        return AdvancePaymentFactory::new();
    }

    protected static function booted(): void
    {
        static::created(function (self $row) {
            if ($row->payment_no === null) {
                $fy = FinancialYear::label($row->created_at);
                $seq = static::where('fy_label', $fy)->count() + 1;
                $row->forceFill([
                    'fy_label' => $fy,
                    'payment_no' => 'MQ/AP/'.$fy.'/'.str_pad((string) $seq, 5, '0', STR_PAD_LEFT),
                ])->saveQuietly();
            }
        });
    }

    /** @return array<string, string> */
    public static function paymentStatuses(): array
    {
        return [
            self::STATUS_POSTED => 'Posted',
            self::STATUS_CANCELLED => 'Cancelled',
            self::STATUS_REVERSED => 'Reversed',
        ];
    }

    /** @return array<string, string> */
    public static function advancePaymentTypes(): array
    {
        return [
            'against_request' => 'Against Advance Request',
            'direct' => 'Direct Advance',
            'odd_item' => 'Odd Item Purchase',
        ];
    }

    /** @return array<string, string> */
    public static function chequeStatuses(): array
    {
        return [
            'issued' => 'Issued',
            'cleared' => 'Cleared',
            'returned_bounced' => 'Returned / Bounced',
            'cancelled' => 'Cancelled',
        ];
    }

    /** @return array<string, string> */
    public static function reversalReasons(): array
    {
        return [
            'wrong_amount' => 'Wrong Amount',
            'wrong_vendor' => 'Wrong Vendor',
            'duplicate_entry' => 'Duplicate Entry',
            'order_cancelled' => 'Order Cancelled',
            'bank_failure' => 'Bank Failure',
        ];
    }

    /** @return array<string, string> */
    public static function cancellationReasons(): array
    {
        return [
            'requested_by_vendor' => 'Requested by Vendor',
            'budget_issue' => 'Budget Issue',
            'wrong_entry' => 'Wrong Entry',
            'order_cancelled' => 'Order Cancelled',
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

    public function advanceRequest(): BelongsTo
    {
        return $this->belongsTo(VendorAdvanceRequest::class, 'vendor_advance_request_id');
    }

    public function inquiry(): BelongsTo
    {
        return $this->belongsTo(VendorPurchaseInquiry::class, 'vendor_purchase_inquiry_id');
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

    public function department(): BelongsTo
    {
        return $this->belongsTo(WorkshopDepartmentMaster::class, 'workshop_department_id');
    }

    public function serviceType(): BelongsTo
    {
        return $this->belongsTo(ServiceTypeMaster::class, 'service_type_id');
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(VendorMaster::class, 'vendor_id');
    }

    public function entryBy(): BelongsTo
    {
        return $this->belongsTo(EmployeeMaster::class, 'entry_by_id');
    }

    public function storeIncharge(): BelongsTo
    {
        return $this->belongsTo(EmployeeMaster::class, 'store_incharge_id');
    }

    public function advisor(): BelongsTo
    {
        return $this->belongsTo(EmployeeMaster::class, 'advisor_id');
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

    public function attachments(): HasMany
    {
        return $this->hasMany(AdvancePaymentAttachment::class, 'advance_payment_id')->orderBy('sequence_no');
    }
}
