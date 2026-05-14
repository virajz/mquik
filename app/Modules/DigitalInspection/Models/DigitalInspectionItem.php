<?php

namespace App\Modules\DigitalInspection\Models;

use App\Modules\InspectionItemGroupMaster\Models\InspectionItemGroupMaster;
use App\Modules\InspectionItemMaster\Models\InspectionItemMaster;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DigitalInspectionItem extends Model
{
    protected $table = 'digital_inspection_items';

    protected $guarded = [];

    public function inspection(): BelongsTo
    {
        return $this->belongsTo(DigitalInspection::class, 'digital_inspection_id');
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
