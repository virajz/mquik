<?php

namespace App\Modules\VehicleInventoryItemMaster\Models;

use App\Concerns\Auditable;
use App\Modules\VehicleInventoryItemMaster\Database\Factories\VehicleInventoryItemMasterFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VehicleInventoryItemMaster extends Model
{
    use Auditable;
    use HasFactory;

    protected $table = 'vehicle_inventory_items';

    protected $guarded = [];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    protected static function newFactory(): VehicleInventoryItemMasterFactory
    {
        return VehicleInventoryItemMasterFactory::new();
    }
}
