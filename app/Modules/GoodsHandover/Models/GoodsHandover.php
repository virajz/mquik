<?php

namespace App\Modules\GoodsHandover\Models;

use App\Concerns\Auditable;
use App\Concerns\Searchable;
use App\Modules\EmployeeMaster\Models\EmployeeMaster;
use App\Modules\FinalWorkOrder\Models\FinalWorkOrder;
use App\Modules\GoodsHandover\Database\Factories\GoodsHandoverFactory;
use App\Modules\GoodsReceipt\Models\GoodsReceipt;
use App\Modules\JobCard\Models\JobCard;
use App\Modules\VendorMaster\Models\VendorMaster;
use App\Modules\WorkshopDepartmentMaster\Models\WorkshopDepartmentMaster;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GoodsHandover extends Model
{
    use Auditable;
    use HasFactory;
    use Searchable;

    public const STATUS_RECEIVED = 'received';

    public const STATUS_PENDING = 'verification_pending';

    public const STATUS_VERIFIED = 'verified';

    public const STATUS_MISMATCH_ACCEPTED = 'mismatch_accepted';

    public const STATUS_MISMATCH_REJECTED = 'mismatch_rejected';

    protected $table = 'goods_handovers';

    protected $guarded = [];

    protected static array $searchableFields = ['handover_no', 'notes', 'jobCard.job_card_no'];

    protected static function newFactory(): GoodsHandoverFactory
    {
        return GoodsHandoverFactory::new();
    }

    protected static function booted(): void
    {
        static::created(function (self $row) {
            if ($row->handover_no === null) {
                $row->forceFill(['handover_no' => 'GHO-'.str_pad((string) $row->id, 5, '0', STR_PAD_LEFT)])->saveQuietly();
            }
        });
    }

    /** @return array<string, string> */
    public static function statuses(): array
    {
        return [
            self::STATUS_RECEIVED => 'Received',
            self::STATUS_PENDING => 'Verification Pending',
            self::STATUS_VERIFIED => 'Verified',
            self::STATUS_MISMATCH_ACCEPTED => 'Mismatch Accepted',
            self::STATUS_MISMATCH_REJECTED => 'Mismatch Rejected',
        ];
    }

    /** @return array<string, string> */
    public static function materialReturnStatuses(): array
    {
        return ['partial_return' => 'Partial Return', 'full_return' => 'Full Return'];
    }

    /** @return array<string, string> */
    public static function returnReasons(): array
    {
        return [
            'excess_issue' => 'Excess Issue',
            'wrong_part_issued' => 'Wrong Part Issued',
            'part_not_required' => 'Part Not Required',
            'defective_part' => 'Defective Part',
        ];
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(WorkshopDepartmentMaster::class, 'workshop_department_id');
    }

    public function handoverBy(): BelongsTo
    {
        return $this->belongsTo(EmployeeMaster::class, 'handover_by_id');
    }

    public function receivedBy(): BelongsTo
    {
        return $this->belongsTo(EmployeeMaster::class, 'received_by_id');
    }

    public function verifiedBy(): BelongsTo
    {
        return $this->belongsTo(EmployeeMaster::class, 'verified_by_id');
    }

    public function partsReturnBy(): BelongsTo
    {
        return $this->belongsTo(EmployeeMaster::class, 'parts_return_by_id');
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(VendorMaster::class, 'vendor_id');
    }

    public function goodsReceipt(): BelongsTo
    {
        return $this->belongsTo(GoodsReceipt::class, 'goods_receipt_id');
    }

    public function finalWorkOrder(): BelongsTo
    {
        return $this->belongsTo(FinalWorkOrder::class, 'final_work_order_id');
    }

    public function jobCard(): BelongsTo
    {
        return $this->belongsTo(JobCard::class, 'job_card_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(GoodsHandoverItem::class, 'goods_handover_id')->orderBy('sequence_no');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(GoodsHandoverAttachment::class, 'goods_handover_id')->orderBy('sequence_no');
    }
}
