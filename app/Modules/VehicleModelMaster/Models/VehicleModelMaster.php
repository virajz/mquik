<?php

namespace App\Modules\VehicleModelMaster\Models;

use App\Concerns\Auditable;
use App\Concerns\Searchable;
use App\Modules\CustomerVehicleMaster\Models\CustomerVehicleMaster;
use App\Modules\VehicleBrandMaster\Models\VehicleBrandMaster;
use App\Modules\VehicleModelMaster\Database\Factories\VehicleModelMasterFactory;
use App\Modules\VehicleSegmentMaster\Models\VehicleSegmentMaster;
use App\Modules\VehicleVariantMaster\Models\VehicleVariantMaster;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VehicleModelMaster extends Model
{
    use Auditable;
    use HasFactory;
    use Searchable;

    protected $table = 'vehicle_models';

    protected $guarded = [];

    protected $casts = [
        'is_active' => 'boolean',
        'service_interval_km' => 'integer',
        'service_interval_months' => 'integer',
    ];

    protected static array $searchableFields = ['name'];

    public function toSearchResult(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->name,
            'subtitle' => $this->brand?->name,
        ];
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(VehicleBrandMaster::class, 'brand_id');
    }

    public function vehicleSegment(): BelongsTo
    {
        return $this->belongsTo(VehicleSegmentMaster::class, 'vehicle_segment_id');
    }

    public function variants(): HasMany
    {
        return $this->hasMany(VehicleVariantMaster::class, 'model_id');
    }

    public function customerVehicles(): HasMany
    {
        return $this->hasMany(CustomerVehicleMaster::class, 'model_id');
    }

    protected static function newFactory(): VehicleModelMasterFactory
    {
        return VehicleModelMasterFactory::new();
    }
}
