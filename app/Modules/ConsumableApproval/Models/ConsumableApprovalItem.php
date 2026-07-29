<?php

namespace App\Modules\ConsumableApproval\Models;

use App\Modules\HsnMaster\Models\HsnMaster;
use App\Modules\OutsideLabourOrder\Models\OutsideLabourOrder;
use App\Modules\SpareMaster\Models\SpareMaster;
use App\Modules\TaxMaster\Models\TaxMaster;
use App\Modules\UnitOfMeasureMaster\Models\UnitOfMeasureMaster;
use App\Modules\VendorPurchaseOrder\Models\VendorPurchaseOrder;
use App\Modules\WorkshopDepartmentMaster\Models\WorkshopDepartmentMaster;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One consumable / labour loss line, with its loss-damage type and photo.
 */
class ConsumableApprovalItem extends Model
{
    protected $table = 'consumable_approval_items';

    protected $guarded = [];

    protected $casts = [
        'quantity' => 'decimal:2',
        'rate' => 'decimal:2',
        'sequence_no' => 'integer',
    ];

    /** @return array<string, string> */
    public static function itemTypes(): array
    {
        return ['spare' => 'Spare / Consumable', 'labour' => 'Labour'];
    }

    public function request(): BelongsTo
    {
        return $this->belongsTo(ConsumableApproval::class, 'consumable_approval_id');
    }

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(VendorPurchaseOrder::class, 'vendor_purchase_order_id');
    }

    public function outsideLabourOrder(): BelongsTo
    {
        return $this->belongsTo(OutsideLabourOrder::class, 'outside_labour_order_id');
    }

    public function spare(): BelongsTo
    {
        return $this->belongsTo(SpareMaster::class, 'spare_id');
    }

    public function uom(): BelongsTo
    {
        return $this->belongsTo(UnitOfMeasureMaster::class, 'uom_id');
    }

    public function hsn(): BelongsTo
    {
        return $this->belongsTo(HsnMaster::class, 'hsn_id');
    }

    public function tax(): BelongsTo
    {
        return $this->belongsTo(TaxMaster::class, 'tax_id');
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(WorkshopDepartmentMaster::class, 'workshop_department_id');
    }
}
