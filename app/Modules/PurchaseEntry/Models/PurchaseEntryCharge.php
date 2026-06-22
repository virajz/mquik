<?php

namespace App\Modules\PurchaseEntry\Models;

use App\Modules\ChargeTypeMaster\Models\ChargeTypeMaster;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseEntryCharge extends Model
{
    protected $table = 'purchase_entry_charges';

    protected $guarded = [];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    public function purchaseEntry(): BelongsTo
    {
        return $this->belongsTo(PurchaseEntry::class, 'purchase_entry_id');
    }

    public function chargeType(): BelongsTo
    {
        return $this->belongsTo(ChargeTypeMaster::class, 'charge_type_id');
    }
}
