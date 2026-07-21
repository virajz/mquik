<?php

namespace App\Modules\PickupDrop\Models;

use App\Modules\PhotoTypeMaster\Models\PhotoTypeMaster;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Vehicle condition evidence, captured at pickup and again at drop. */
class PickupDropPhoto extends Model
{
    protected $table = 'pickup_drop_photos';

    protected $guarded = [];

    protected $casts = [
        'size_bytes' => 'integer',
        'sequence_no' => 'integer',
    ];

    public function pickupDrop(): BelongsTo
    {
        return $this->belongsTo(PickupDrop::class);
    }

    public function photoType(): BelongsTo
    {
        return $this->belongsTo(PhotoTypeMaster::class, 'photo_type_id');
    }
}
