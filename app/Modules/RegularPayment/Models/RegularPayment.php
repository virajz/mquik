<?php

namespace App\Modules\RegularPayment\Models;

use App\Concerns\Auditable;
use App\Concerns\Searchable;
use App\Modules\BankMaster\Models\BankMaster;
use App\Modules\ChequeBounceReasonMaster\Models\ChequeBounceReasonMaster;
use App\Modules\EmployeeMaster\Models\EmployeeMaster;
use App\Modules\PaymentCancellationReasonMaster\Models\PaymentCancellationReasonMaster;
use App\Modules\PaymentHoldReasonMaster\Models\PaymentHoldReasonMaster;
use App\Modules\PaymentModeMaster\Models\PaymentModeMaster;
use App\Modules\PurchaseEntry\Models\PurchaseEntry;
use App\Modules\RegularPayment\Database\Factories\RegularPaymentFactory;
use App\Modules\VendorMaster\Models\VendorMaster;
use App\Support\FinancialYear;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RegularPayment extends Model
{
    use Auditable;
    use HasFactory;
    use Searchable;

    protected $table = 'regular_payments';

    protected $guarded = [];

    protected $casts = [
        'amount' => 'decimal:2',
        'cheque_date' => 'date',
        'paid_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    protected static array $searchableFields = ['payment_no', 'reference_no', 'cheque_no', 'notes', 'vendor.name'];

    protected static function newFactory(): RegularPaymentFactory
    {
        return RegularPaymentFactory::new();
    }

    protected static function booted(): void
    {
        static::created(function (self $row) {
            if ($row->payment_no === null) {
                $fy = FinancialYear::label($row->created_at);
                $seq = static::where('fy_label', $fy)->count() + 1;
                $row->forceFill([
                    'fy_label' => $fy,
                    'payment_no' => 'MQ/PV/'.$fy.'/'.str_pad((string) $seq, 5, '0', STR_PAD_LEFT),
                ])->saveQuietly();
            }
        });
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(VendorMaster::class, 'vendor_id');
    }

    public function paidBy(): BelongsTo
    {
        return $this->belongsTo(EmployeeMaster::class, 'paid_by_id');
    }

    public function advancePayment(): BelongsTo
    {
        return $this->belongsTo(self::class, 'advance_payment_id');
    }

    public function purchaseEntry(): BelongsTo
    {
        return $this->belongsTo(PurchaseEntry::class, 'purchase_entry_id');
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

    public function holdReason(): BelongsTo
    {
        return $this->belongsTo(PaymentHoldReasonMaster::class, 'payment_hold_reason_id');
    }

    public function cancellationReason(): BelongsTo
    {
        return $this->belongsTo(PaymentCancellationReasonMaster::class, 'payment_cancellation_reason_id');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(RegularPaymentAttachment::class, 'regular_payment_id');
    }

    /** @return array<string, string> */
    public static function statuses(): array
    {
        return [
            'draft' => 'Draft',
            'on_hold' => 'On Hold',
            'paid' => 'Paid',
            'cancelled' => 'Cancelled',
        ];
    }

    /** @return array<string, string> */
    public static function chequeStatuses(): array
    {
        return [
            'issued' => 'Issued',
            'cleared' => 'Cleared',
            'bounced' => 'Returned / Bounced',
            'cancelled' => 'Cancelled',
        ];
    }

    /** @return array<string, string> */
    public static function attachmentTypes(): array
    {
        return [
            'cheque_copy' => 'Cheque Copy',
            'utr_screenshot' => 'UTR Screenshot',
            'payment_advice' => 'Payment Advice',
        ];
    }
}
