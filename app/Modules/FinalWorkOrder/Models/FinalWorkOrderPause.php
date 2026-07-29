<?php

namespace App\Modules\FinalWorkOrder\Models;

use App\Modules\WorkOrderHoldReasonMaster\Models\WorkOrderHoldReasonMaster;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FinalWorkOrderPause extends Model
{
    protected $table = 'final_work_order_pauses';

    protected $guarded = [];

    protected $casts = [
        'paused_at' => 'datetime',
        'resumed_at' => 'datetime',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(FinalWorkOrder::class, 'final_work_order_id');
    }

    public function holdReason(): BelongsTo
    {
        return $this->belongsTo(WorkOrderHoldReasonMaster::class, 'hold_reason_id');
    }
}
