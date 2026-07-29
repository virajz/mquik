<?php

namespace App\Modules\OutsideLabourOrder\Models;

use App\Modules\InspectionItemGroupMaster\Models\InspectionItemGroupMaster;
use App\Modules\InspectionItemMaster\Models\InspectionItemMaster;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OutsideLabourOrderItem extends Model
{
    protected $table = 'outside_labour_order_items';

    protected $guarded = [];

    protected $casts = ['hours' => 'decimal:2'];

    public function order(): BelongsTo
    {
        return $this->belongsTo(OutsideLabourOrder::class, 'outside_labour_order_id');
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
