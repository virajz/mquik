<?php

namespace App\Modules\ChallanEntry\Models;

use App\Modules\ChargeTypeMaster\Models\ChargeTypeMaster;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChallanCharge extends Model
{
    protected $table = 'challan_charges';

    protected $guarded = [];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    public function challan(): BelongsTo
    {
        return $this->belongsTo(Challan::class, 'challan_id');
    }

    public function chargeType(): BelongsTo
    {
        return $this->belongsTo(ChargeTypeMaster::class, 'charge_type_id');
    }
}
