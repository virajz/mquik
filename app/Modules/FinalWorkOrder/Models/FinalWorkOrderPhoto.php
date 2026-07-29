<?php

namespace App\Modules\FinalWorkOrder\Models;

use App\Modules\PhotoTypeMaster\Models\PhotoTypeMaster;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Order-level photo evidence (front/rear/side/damage/fault), distinct from the
 * before/after pair stored against each checklist item.
 */
class FinalWorkOrderPhoto extends Model
{
    protected $table = 'final_work_order_photos';

    protected $guarded = [];

    protected $casts = [
        'size_bytes' => 'integer',
        'sequence_no' => 'integer',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(FinalWorkOrder::class, 'final_work_order_id');
    }

    public function photoType(): BelongsTo
    {
        return $this->belongsTo(PhotoTypeMaster::class, 'photo_type_id');
    }
}
