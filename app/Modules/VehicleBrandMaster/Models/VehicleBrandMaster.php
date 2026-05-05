<?php

namespace App\Modules\VehicleBrandMaster\Models;

use App\Modules\VehicleBrandMaster\Database\Factories\VehicleBrandMasterFactory;
use App\Modules\VehicleModelMaster\Models\VehicleModelMaster;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VehicleBrandMaster extends Model
{
    use HasFactory;

    protected $table = 'vehicle_brands';

    protected $guarded = [];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function models(): HasMany
    {
        return $this->hasMany(VehicleModelMaster::class, 'brand_id');
    }

    protected static function newFactory(): VehicleBrandMasterFactory
    {
        return VehicleBrandMasterFactory::new();
    }
}
