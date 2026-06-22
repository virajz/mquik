<?php

namespace App\Modules\VehicleInspectionOrder\Models;

use App\Modules\WorkOrderHoldReasonMaster\Models\WorkOrderHoldReasonMaster;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VehicleInspectionOrderPause extends Model
{
    protected $table = 'vehicle_inspection_order_pauses';

    protected $guarded = [];

    protected $casts = [
        'paused_at' => 'datetime',
        'resumed_at' => 'datetime',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(VehicleInspectionOrder::class, 'vehicle_inspection_order_id');
    }

    public function holdReason(): BelongsTo
    {
        return $this->belongsTo(WorkOrderHoldReasonMaster::class, 'hold_reason_id');
    }
}
