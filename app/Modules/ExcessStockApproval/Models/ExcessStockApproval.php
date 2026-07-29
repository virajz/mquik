<?php

namespace App\Modules\ExcessStockApproval\Models;

use App\Concerns\Auditable;
use App\Concerns\Searchable;
use App\Modules\EmployeeMaster\Models\EmployeeMaster;
use App\Modules\ExcessStockApproval\Database\Factories\ExcessStockApprovalFactory;
use App\Modules\FollowUpModeMaster\Models\FollowUpModeMaster;
use App\Modules\GoodsHandover\Models\GoodsHandover;
use App\Modules\GoodsReceipt\Models\GoodsReceipt;
use App\Modules\ServiceTypeMaster\Models\ServiceTypeMaster;
use App\Modules\WorkshopDepartmentMaster\Models\WorkshopDepartmentMaster;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ExcessStockApproval extends Model
{
    use Auditable;
    use HasFactory;
    use Searchable;

    public const STATUS_REQUESTED = 'requested';

    public const STATUS_ON_HOLD = 'on_hold';

    public const STATUS_UNDER_REVIEW = 'under_review';

    public const STATUS_VERBAL_CLARIFICATION = 'verbal_clarification';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_REJECTED = 'rejected';

    protected $table = 'excess_stock_approvals';

    protected $guarded = [];

    protected $casts = [
        'requested_at' => 'datetime',
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
    ];

    protected static array $searchableFields = ['request_no', 'purchase_invoice_reference', 'notes'];

    protected static function newFactory(): ExcessStockApprovalFactory
    {
        return ExcessStockApprovalFactory::new();
    }

    protected static function booted(): void
    {
        static::created(function (self $row) {
            if ($row->request_no === null) {
                $row->forceFill(['request_no' => 'ESA-'.str_pad((string) $row->id, 5, '0', STR_PAD_LEFT)])->saveQuietly();
            }
        });
    }

    /** @return array<string, string> */
    public static function statuses(): array
    {
        return [
            self::STATUS_REQUESTED => 'Requested',
            self::STATUS_ON_HOLD => 'On Hold',
            self::STATUS_UNDER_REVIEW => 'Under Review',
            self::STATUS_VERBAL_CLARIFICATION => 'Verbal Clarification Required',
            self::STATUS_APPROVED => 'Approved',
            self::STATUS_REJECTED => 'Rejected',
        ];
    }

    /** @return array<string, string> */
    public static function excessStockReasons(): array
    {
        return [
            'wrong_purchase' => 'Wrong Purchase',
            'excess_purchase' => 'Excess Purchase',
            'duplicate_purchase' => 'Duplicate Purchase',
            'order_cancellation' => 'Customer Order Cancellation',
            'vehicle_sold' => 'Vehicle Sold',
            'vehicle_scrapped' => 'Vehicle Scrapped',
            'non_returnable' => 'Non-Returnable Item',
            'vendor_return_rejected' => 'Vendor Return Rejected',
            'return_window_expired' => 'Return Window Expired',
            'warranty_expired' => 'Warranty Expired',
            'open_packing' => 'Open Packing',
        ];
    }

    /** Reasons that mark the value as dead (unrecoverable) stock. @return list<string> */
    public static function deadStockReasons(): array
    {
        return ['non_returnable', 'vendor_return_rejected', 'return_window_expired', 'warranty_expired', 'open_packing', 'vehicle_scrapped'];
    }

    /** @return array<string, string> */
    public static function vendorRejectionReasons(): array
    {
        return ['fitment_issue' => 'Fitment Issue', 'damaged_at_workshop' => 'Damaged at Workshop Premise'];
    }

    /** @return array<string, string> */
    public static function priorities(): array
    {
        return ['normal' => 'Normal', 'high' => 'High', 'urgent' => 'Urgent'];
    }

    public function goodsHandover(): BelongsTo
    {
        return $this->belongsTo(GoodsHandover::class, 'goods_handover_id');
    }

    public function goodsReceipt(): BelongsTo
    {
        return $this->belongsTo(GoodsReceipt::class, 'goods_receipt_id');
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(WorkshopDepartmentMaster::class, 'workshop_department_id');
    }

    public function serviceType(): BelongsTo
    {
        return $this->belongsTo(ServiceTypeMaster::class, 'service_type_id');
    }

    public function followUpMode(): BelongsTo
    {
        return $this->belongsTo(FollowUpModeMaster::class, 'follow_up_mode_id');
    }

    public function advisor(): BelongsTo
    {
        return $this->belongsTo(EmployeeMaster::class, 'advisor_id');
    }

    public function storeIncharge(): BelongsTo
    {
        return $this->belongsTo(EmployeeMaster::class, 'store_incharge_id');
    }

    public function storeExecutive(): BelongsTo
    {
        return $this->belongsTo(EmployeeMaster::class, 'store_executive_id');
    }

    public function mistakeBy(): BelongsTo
    {
        return $this->belongsTo(EmployeeMaster::class, 'mistake_by_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(ExcessStockApprovalItem::class, 'excess_stock_approval_id')->orderBy('sequence_no');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(ExcessStockApprovalAttachment::class, 'excess_stock_approval_id')->orderBy('sequence_no');
    }
}
