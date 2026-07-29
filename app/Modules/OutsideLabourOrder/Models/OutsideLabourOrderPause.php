<?php

namespace App\Modules\OutsideLabourOrder\Models;

use App\Modules\WorkOrderHoldReasonMaster\Models\WorkOrderHoldReasonMaster;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OutsideLabourOrderPause extends Model
{
    protected $table = 'outside_labour_order_pauses';

    protected $guarded = [];

    protected $casts = [
        'paused_at' => 'datetime',
        'resumed_at' => 'datetime',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(OutsideLabourOrder::class, 'outside_labour_order_id');
    }

    public function holdReason(): BelongsTo
    {
        return $this->belongsTo(WorkOrderHoldReasonMaster::class, 'hold_reason_id');
    }
}
