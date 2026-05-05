<?php

namespace App\Modules\VehicleModelMaster\Models;

use App\Modules\VehicleBrandMaster\Models\VehicleBrandMaster;
use App\Modules\VehicleModelMaster\Database\Factories\VehicleModelMasterFactory;
use App\Modules\VehicleVariantMaster\Models\VehicleVariantMaster;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VehicleModelMaster extends Model
{
    use HasFactory;

    protected $table = 'vehicle_models';

    protected $guarded = [];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function brand(): BelongsTo
    {
        return $this->belongsTo(VehicleBrandMaster::class, 'brand_id');
    }

    public function variants(): HasMany
    {
        return $this->hasMany(VehicleVariantMaster::class, 'model_id');
    }

    protected static function newFactory(): VehicleModelMasterFactory
    {
        return VehicleModelMasterFactory::new();
    }
}
