<?php

namespace App\Modules\VehicleInspectionOrder\Models;

use App\Modules\InspectionItemGroupMaster\Models\InspectionItemGroupMaster;
use App\Modules\InspectionItemMaster\Models\InspectionItemMaster;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VehicleInspectionOrderItem extends Model
{
    protected $table = 'vehicle_inspection_order_items';

    protected $guarded = [];

    public function order(): BelongsTo
    {
        return $this->belongsTo(VehicleInspectionOrder::class, 'vehicle_inspection_order_id');
    }

    public function inspectionItem(): BelongsTo
    {
        return $this->belongsTo(InspectionItemMaster::class, 'inspection_item_id');
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(InspectionItemGroupMaster::class, 'inspection_item_group_id');
    }
}
