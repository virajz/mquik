<?php

namespace App\Modules\VehicleSegmentMaster\Models;

use App\Concerns\Auditable;
use App\Modules\VehicleSegmentMaster\Database\Factories\VehicleSegmentMasterFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VehicleSegmentMaster extends Model
{
    use Auditable;
    use HasFactory;

    protected $table = 'vehicle_segments';

    protected $guarded = [];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    protected static function newFactory(): VehicleSegmentMasterFactory
    {
        return VehicleSegmentMasterFactory::new();
    }
}
