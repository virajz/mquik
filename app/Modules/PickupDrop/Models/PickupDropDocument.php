<?php

namespace App\Modules\PickupDrop\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A document line snapshotted from the chosen checklist template — kept as a
 * copy so later edits to the template never rewrite collection history.
 */
class PickupDropDocument extends Model
{
    protected $table = 'pickup_drop_documents';

    protected $guarded = [];

    protected $casts = [
        'is_required' => 'boolean',
        'is_collected' => 'boolean',
        'sequence_no' => 'integer',
    ];

    public function pickupDrop(): BelongsTo
    {
        return $this->belongsTo(PickupDrop::class);
    }
}
