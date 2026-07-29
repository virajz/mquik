<?php

namespace App\Modules\GoodsReceipt\Models;

use App\Concerns\Auditable;
use App\Concerns\Searchable;
use App\Modules\EmployeeMaster\Models\EmployeeMaster;
use App\Modules\FollowUpModeMaster\Models\FollowUpModeMaster;
use App\Modules\GoodsReceipt\Database\Factories\GoodsReceiptFactory;
use App\Modules\VendorMaster\Models\VendorMaster;
use App\Modules\VendorPurchaseInquiry\Models\VendorPurchaseInquiry;
use App\Modules\VendorPurchaseOrder\Models\VendorPurchaseOrder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GoodsReceipt extends Model
{
    use Auditable;
    use HasFactory;
    use Searchable;

    public const STATUS_PENDING = 'verification_pending';

    public const STATUS_VERIFIED = 'verified';

    public const STATUS_MISMATCH_ACCEPTED = 'mismatch_accepted';

    public const STATUS_MISMATCH_REJECTED = 'mismatch_rejected';

    protected $table = 'goods_receipts';

    protected $guarded = [];

    protected static array $searchableFields = ['grn_no', 'notes'];

    protected static function newFactory(): GoodsReceiptFactory
    {
        return GoodsReceiptFactory::new();
    }

    protected static function booted(): void
    {
        static::created(function (self $row) {
            if ($row->grn_no === null) {
                $row->forceFill(['grn_no' => 'GRN-'.str_pad((string) $row->id, 5, '0', STR_PAD_LEFT)])->saveQuietly();
            }
        });
    }

    /** @return array<string, string> */
    public static function statuses(): array
    {
        return [
            self::STATUS_PENDING => 'Verification Pending',
            self::STATUS_VERIFIED => 'Verified',
            self::STATUS_MISMATCH_ACCEPTED => 'Mismatch Accepted',
            self::STATUS_MISMATCH_REJECTED => 'Mismatch Rejected',
        ];
    }

    /** @return array<string, string> */
    public static function receiptTypes(): array
    {
        return ['against_po' => 'Against PO', 'direct_receipt' => 'Direct Receipt'];
    }

    /** @return array<string, string> */
    public static function deliveryPerformances(): array
    {
        return ['on_time' => 'On Time', 'delayed' => 'Delayed'];
    }

    /** @return array<string, string> */
    public static function approvalAuthorities(): array
    {
        return [
            'store_executive' => 'Store Executive',
            'parts_manager' => 'Parts Manager',
            'store_manager' => 'Store Manager',
        ];
    }

    /** @return array<string, string> */
    public static function vendorCategories(): array
    {
        return ['preferred' => 'Preferred Vendor', 'approved' => 'Approved Vendor', 'backup' => 'Backup Vendor'];
    }

    /** @return array<string, string> */
    public static function vendorRatingTypes(): array
    {
        return [
            'quality' => 'Quality Product',
            'price' => 'Reasonable Price',
            'delivery' => 'Ontime Delivery',
            'support' => 'Handhold Support',
        ];
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(VendorMaster::class, 'vendor_id');
    }

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(VendorPurchaseOrder::class, 'vendor_purchase_order_id');
    }

    public function inquiry(): BelongsTo
    {
        return $this->belongsTo(VendorPurchaseInquiry::class, 'vendor_purchase_inquiry_id');
    }

    public function receivedBy(): BelongsTo
    {
        return $this->belongsTo(EmployeeMaster::class, 'received_by_id');
    }

    public function verifiedBy(): BelongsTo
    {
        return $this->belongsTo(EmployeeMaster::class, 'verified_by_id');
    }

    public function followUpMode(): BelongsTo
    {
        return $this->belongsTo(FollowUpModeMaster::class, 'follow_up_mode_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(GoodsReceiptItem::class, 'goods_receipt_id')->orderBy('sequence_no');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(GoodsReceiptAttachment::class, 'goods_receipt_id')->orderBy('sequence_no');
    }
}
