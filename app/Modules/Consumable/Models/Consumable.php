<?php

namespace App\Modules\Consumable\Models;

use App\Concerns\Auditable;
use App\Concerns\Searchable;
use App\Modules\ChallanEntry\Models\Challan;
use App\Modules\Consumable\Database\Factories\ConsumableFactory;
use App\Modules\ConsumableCategoryMaster\Models\ConsumableCategoryMaster;
use App\Modules\EmployeeMaster\Models\EmployeeMaster;
use App\Modules\JobCard\Models\JobCard;
use App\Modules\LossReasonMaster\Models\LossReasonMaster;
use App\Modules\LossTypeMaster\Models\LossTypeMaster;
use App\Modules\PurchaseEntry\Models\PurchaseEntry;
use App\Modules\VendorMaster\Models\VendorMaster;
use App\Modules\WorkshopDepartmentMaster\Models\WorkshopDepartmentMaster;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Consumable extends Model
{
    use Auditable;
    use HasFactory;
    use Searchable;

    protected $table = 'consumables';

    protected $guarded = [];

    protected $casts = [
        'parts_value' => 'decimal:2',
        'labour_value' => 'decimal:2',
        'tax_total' => 'decimal:2',
        'total_value' => 'decimal:2',
        'consumed_at' => 'datetime',
        'approved_at' => 'datetime',
    ];

    protected static array $searchableFields = ['consumable_no', 'notes', 'jobCard.job_card_no'];

    protected static function newFactory(): ConsumableFactory
    {
        return ConsumableFactory::new();
    }

    protected static function booted(): void
    {
        static::created(function (self $row) {
            if ($row->consumable_no === null) {
                $row->forceFill([
                    'consumable_no' => 'CN-'.str_pad((string) $row->id, 5, '0', STR_PAD_LEFT),
                ])->saveQuietly();
            }
        });
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ConsumableCategoryMaster::class, 'consumable_category_id');
    }

    public function jobCard(): BelongsTo
    {
        return $this->belongsTo(JobCard::class, 'job_card_id');
    }

    public function challan(): BelongsTo
    {
        return $this->belongsTo(Challan::class, 'challan_id');
    }

    public function purchaseEntry(): BelongsTo
    {
        return $this->belongsTo(PurchaseEntry::class, 'purchase_entry_id');
    }

    public function lossType(): BelongsTo
    {
        return $this->belongsTo(LossTypeMaster::class, 'loss_type_id');
    }

    public function lossReason(): BelongsTo
    {
        return $this->belongsTo(LossReasonMaster::class, 'loss_reason_id');
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(WorkshopDepartmentMaster::class, 'department_id');
    }

    public function advisor(): BelongsTo
    {
        return $this->belongsTo(EmployeeMaster::class, 'advisor_id');
    }

    public function technician(): BelongsTo
    {
        return $this->belongsTo(EmployeeMaster::class, 'technician_id');
    }

    public function storeIncharge(): BelongsTo
    {
        return $this->belongsTo(EmployeeMaster::class, 'store_incharge_id');
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(VendorMaster::class, 'vendor_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(ConsumableItem::class, 'consumable_id')->orderBy('sequence_no');
    }

    /** @return array<string, string> */
    public static function approvalAuthorities(): array
    {
        return [
            'store_manager' => 'Store Manager',
            'service_advisor' => 'Service Advisor',
            'workshop_manager' => 'Workshop Manager',
            'admin' => 'Admin',
            'owner' => 'Owner',
        ];
    }

    /** @return array<string, string> */
    public static function approvalStatuses(): array
    {
        return [
            'requested' => 'Requested',
            'approved' => 'Approved',
            'rejected' => 'Rejected',
            'on_hold' => 'On Hold',
        ];
    }

    /** @return array<string, string> */
    public static function communicationModes(): array
    {
        return [
            'whatsapp' => 'WhatsApp',
            'email' => 'Email',
            'physical_signature' => 'Physical Signature',
        ];
    }
}
