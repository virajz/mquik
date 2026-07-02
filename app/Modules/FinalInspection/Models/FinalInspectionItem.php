<?php

namespace App\Modules\FinalInspection\Models;

use App\Modules\InspectionItemGroupMaster\Models\InspectionItemGroupMaster;
use App\Modules\InspectionItemMaster\Models\InspectionItemMaster;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FinalInspectionItem extends Model
{
    protected $table = 'final_inspection_items';

    protected $guarded = [];

    public function finalInspection(): BelongsTo
    {
        return $this->belongsTo(FinalInspection::class, 'final_inspection_id');
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
