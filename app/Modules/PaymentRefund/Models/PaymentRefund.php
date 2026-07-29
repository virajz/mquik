<?php

namespace App\Modules\PaymentRefund\Models;

use App\Concerns\Auditable;
use App\Concerns\Searchable;
use App\Modules\AdvancePayment\Models\AdvancePayment;
use App\Modules\BankMaster\Models\BankMaster;
use App\Modules\ChequeBounceReasonMaster\Models\ChequeBounceReasonMaster;
use App\Modules\CustomerMaster\Models\CustomerMaster;
use App\Modules\CustomerVehicleMaster\Models\CustomerVehicleMaster;
use App\Modules\EmployeeMaster\Models\EmployeeMaster;
use App\Modules\GoodsReturnNote\Models\GoodsReturnNote;
use App\Modules\JobCard\Models\JobCard;
use App\Modules\OutsideLabourReturn\Models\OutsideLabourReturn;
use App\Modules\PaymentRefund\Database\Factories\PaymentRefundFactory;
use App\Modules\VendorMaster\Models\VendorMaster;
use App\Modules\VendorPurchaseOrder\Models\VendorPurchaseOrder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PaymentRefund extends Model
{
    use Auditable;
    use HasFactory;
    use Searchable;

    public const STATUS_REQUESTED = 'requested';

    public const STATUS_ON_HOLD = 'on_hold';

    public const STATUS_REFUNDED = 'refunded';

    public const STATUS_REJECTED = 'rejected';

    public const STATUS_CANCELLED = 'cancelled';

    protected $table = 'payment_refunds';

    protected $guarded = [];

    protected $casts = [
        'amount' => 'decimal:2',
        'cheque_date' => 'date',
        'requested_at' => 'datetime',
        'refunded_at' => 'datetime',
        'rejected_at' => 'datetime',
    ];

    protected static array $searchableFields = ['refund_no', 'reference_no', 'cheque_no', 'notes'];

    protected static function newFactory(): PaymentRefundFactory
    {
        return PaymentRefundFactory::new();
    }

    protected static function booted(): void
    {
        static::created(function (self $row) {
            if ($row->refund_no === null) {
                $row->forceFill(['refund_no' => 'PRF-'.str_pad((string) $row->id, 5, '0', STR_PAD_LEFT)])->saveQuietly();
            }
        });
    }

    /** @return array<string, string> */
    public static function statuses(): array
    {
        return [
            self::STATUS_REQUESTED => 'Requested',
            self::STATUS_ON_HOLD => 'On Hold',
            self::STATUS_REFUNDED => 'Refunded',
            self::STATUS_REJECTED => 'Rejected',
            self::STATUS_CANCELLED => 'Cancelled',
        ];
    }

    /** @return array<string, string> */
    public static function refundAgainstOptions(): array
    {
        return ['advance_payment' => 'Advance Payment', 'regular_payment' => 'Regular Payment'];
    }

    /** @return array<string, string> */
    public static function refundTypes(): array
    {
        return [
            'advance_security' => 'Advance Amt / Security Deposit',
            'excess_payment' => 'Excess Payment',
            'duplicate_payment' => 'Duplicate Payment',
            'order_cancel' => 'Order Cancel',
            'invoice_revision' => 'Invoice Revision',
            'spares_purchase_return' => 'Spares Purchase Return',
            'labour_purchase_return' => 'Labour Purchase Return',
            'compensation' => 'Compensation Refund',
        ];
    }

    /** @return array<string, string> */
    public static function priorities(): array
    {
        return ['normal' => 'Normal', 'high' => 'High', 'urgent' => 'Urgent'];
    }

    /** @return array<string, string> */
    public static function refundModes(): array
    {
        return [
            'cash' => 'Cash', 'cheque' => 'Cheque', 'neft' => 'NEFT', 'rtgs' => 'RTGS',
            'imps' => 'IMPS', 'upi' => 'UPI', 'bank_transfer' => 'Bank Transfer',
        ];
    }

    /** @return array<string, string> */
    public static function chequeStatuses(): array
    {
        return ['cleared' => 'Cleared', 'bounce' => 'Bounce'];
    }

    /** @return array<string, string> */
    public static function rejectionReasons(): array
    {
        return [
            'physical_damage' => 'Physical Damage',
            'fitment_issue' => 'Fitment Issue',
            'adjusted_next_invoice' => 'Adjusted in Next Invoice',
        ];
    }

    /** @return array<string, string> */
    public static function holdReasons(): array
    {
        return ['vendor_dispute' => 'Vendor Dispute', 'pending_documentation' => 'Pending Documentation'];
    }

    /** @return array<string, string> */
    public static function cancellationReasons(): array
    {
        return [
            'payment_failed' => 'Payment Failed',
            'cheque_return' => 'Cheque Return',
            'wrong_vendor' => 'Wrong Vendor Selected',
            'wrong_amount' => 'Wrong Amount Selected',
            'duplicate_entry' => 'Duplicate Entry',
        ];
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(VendorMaster::class, 'vendor_id');
    }

    public function refundBy(): BelongsTo
    {
        return $this->belongsTo(EmployeeMaster::class, 'refund_by_id');
    }

    public function storeIncharge(): BelongsTo
    {
        return $this->belongsTo(EmployeeMaster::class, 'store_incharge_id');
    }

    public function advisor(): BelongsTo
    {
        return $this->belongsTo(EmployeeMaster::class, 'advisor_id');
    }

    public function advancePayment(): BelongsTo
    {
        return $this->belongsTo(AdvancePayment::class, 'advance_payment_id');
    }

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(VendorPurchaseOrder::class, 'vendor_purchase_order_id');
    }

    public function goodsReturnNote(): BelongsTo
    {
        return $this->belongsTo(GoodsReturnNote::class, 'goods_return_note_id');
    }

    public function outsideLabourReturn(): BelongsTo
    {
        return $this->belongsTo(OutsideLabourReturn::class, 'outside_labour_return_id');
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
        return $this->hasMany(PaymentRefundAttachment::class, 'payment_refund_id')->orderBy('sequence_no');
    }
}
