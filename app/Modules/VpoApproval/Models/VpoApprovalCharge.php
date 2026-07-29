<?php

namespace App\Modules\VpoApproval\Models;

use App\Modules\ChargeTypeMaster\Models\ChargeTypeMaster;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VpoApprovalCharge extends Model
{
    protected $table = 'vpo_approval_charges';

    protected $guarded = [];

    protected $casts = ['amount' => 'decimal:2', 'sequence_no' => 'integer'];

    public function approval(): BelongsTo
    {
        return $this->belongsTo(VpoApproval::class, 'vpo_approval_id');
    }

    public function chargeType(): BelongsTo
    {
        return $this->belongsTo(ChargeTypeMaster::class, 'charge_type_id');
    }
}
