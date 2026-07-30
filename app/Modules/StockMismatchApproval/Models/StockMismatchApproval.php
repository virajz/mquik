<?php

namespace App\Modules\StockMismatchApproval\Models;

use App\Concerns\Auditable;
use App\Concerns\Searchable;
use App\Modules\EmployeeMaster\Models\EmployeeMaster;
use App\Modules\StockCounting\Models\StockCount;
use App\Modules\StockMismatchApproval\Database\Factories\StockMismatchApprovalFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StockMismatchApproval extends Model
{
    use Auditable;
    use HasFactory;
    use Searchable;

    public const STATUS_REQUESTED = 'requested';

    public const STATUS_UNDER_REVIEW = 'under_review';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_REJECTED = 'rejected';

    public const STATUS_CANCELLED = 'cancelled';

    protected $table = 'stock_mismatch_approvals';

    protected $guarded = [];

    protected $casts = [
        'requested_at' => 'datetime',
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    protected static array $searchableFields = ['approval_no', 'notes'];

    protected static function newFactory(): StockMismatchApprovalFactory
    {
        return StockMismatchApprovalFactory::new();
    }

    protected static function booted(): void
    {
        static::created(function (self $row) {
            if ($row->approval_no === null) {
                $row->forceFill([
                    'approval_no' => 'SMA-'.str_pad((string) $row->id, 5, '0', STR_PAD_LEFT),
                ])->saveQuietly();
            }
        });
    }

    /** @return array<string, string> */
    public static function approvalStatuses(): array
    {
        return [
            self::STATUS_REQUESTED => 'Requested',
            self::STATUS_UNDER_REVIEW => 'Under Review',
            self::STATUS_APPROVED => 'Approved',
            self::STATUS_REJECTED => 'Rejected',
            self::STATUS_CANCELLED => 'Cancelled',
        ];
    }

    /** @return array<string, string> */
    public static function varianceReasons(): array
    {
        return [
            'data_entry_error' => 'Data Entry Error',
            'wrong_issue' => 'Wrong Issue',
            'wrong_barcode' => 'Wrong Barcode',
            'damaged_parts' => 'Damaged Parts',
            'misplaced_item' => 'Misplaced Item',
            'expired_item' => 'Expired Item',
            'theft' => 'Theft',
            'duplicate_entry' => 'Duplicate Entry',
            'no_purchase_invoice' => 'No Purchase Invoice',
            'forgot_sales_issue' => 'Forgot Sales Issue',
            'technical_error' => 'Technical Error',
            'packing_difference' => 'Packing Difference',
            'vendor_short_supply' => 'Vendor Short Supply',
            'cn_pending' => 'CN Pending',
            'issue_pending' => 'Issue Pending',
            'unknown' => 'Unknown',
        ];
    }

    /** @return array<string, string> */
    public static function managementResponses(): array
    {
        return [
            'adjust' => 'Adjust',
            'on_hold' => 'On Hold',
            'recount' => 'Recount',
            'reinvestigate' => 'Reinvestigate',
            'write_off' => 'Write Off',
        ];
    }

    /** @return array<string, string> */
    public static function recountOutcomes(): array
    {
        return [
            'genuine_difference' => 'Genuine Difference',
            'system_error' => 'System Error',
            'user_error' => 'User Error',
            'vendor_error' => 'Vendor Error',
            'theft_confirmed' => 'Theft Confirmed',
            'duplicate_posting' => 'Duplicate Posting',
            'no_difference' => 'No Difference',
        ];
    }

    /** @return array<string, string> */
    public static function adjustmentMethods(): array
    {
        return [
            'foc_purchase' => 'FOC Purchase',
            'issue_consumption' => 'Issue Consumption',
        ];
    }

    /** @return array<string, string> */
    public static function communicationModes(): array
    {
        return [
            'whatsapp' => 'WhatsApp',
            'email' => 'Email',
            'phone_call' => 'Phone Call',
        ];
    }

    public function stockCount(): BelongsTo
    {
        return $this->belongsTo(StockCount::class, 'stock_count_id');
    }

    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(EmployeeMaster::class, 'requested_by_id');
    }

    public function requestedTo(): BelongsTo
    {
        return $this->belongsTo(EmployeeMaster::class, 'requested_to_id');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(StockMismatchApprovalAttachment::class, 'stock_mismatch_approval_id')->orderBy('sequence_no');
    }
}
