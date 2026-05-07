<?php

namespace App\Modules\VehicleColorMaster\Models;

use App\Concerns\Auditable;
use App\Modules\VehicleColorMaster\Database\Factories\VehicleColorMasterFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VehicleColorMaster extends Model
{
    use Auditable;
    use HasFactory;

    protected $table = 'vehicle_colors';

    protected $guarded = [];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    protected static function newFactory(): VehicleColorMasterFactory
    {
        return VehicleColorMasterFactory::new();
    }
}
