<?php

namespace App\Modules\JobCard\Models;

use App\Modules\VehicleInventoryItemMaster\Models\VehicleInventoryItemMaster;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JobCardInventoryItem extends Model
{
    protected $table = 'job_card_inventory_items';

    protected $guarded = [];

    protected $casts = [
        'is_present' => 'boolean',
    ];

    public function jobCard(): BelongsTo
    {
        return $this->belongsTo(JobCard::class, 'job_card_id');
    }

    public function vehicleInventoryItem(): BelongsTo
    {
        return $this->belongsTo(VehicleInventoryItemMaster::class, 'vehicle_inventory_item_id');
    }
}
