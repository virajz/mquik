<?php

namespace App\Modules\VehicleInspectionOrder\Models;

use App\Modules\PhotoTypeMaster\Models\PhotoTypeMaster;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Order-level photo evidence (front/rear/side/damage/fault), distinct from the
 * before/after pair stored against each checklist item.
 */
class VehicleInspectionOrderPhoto extends Model
{
    protected $table = 'vehicle_inspection_order_photos';

    protected $guarded = [];

    protected $casts = [
        'size_bytes' => 'integer',
        'sequence_no' => 'integer',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(VehicleInspectionOrder::class, 'vehicle_inspection_order_id');
    }

    public function photoType(): BelongsTo
    {
        return $this->belongsTo(PhotoTypeMaster::class, 'photo_type_id');
    }
}
