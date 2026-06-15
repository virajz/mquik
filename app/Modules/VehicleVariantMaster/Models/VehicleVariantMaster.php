<?php

namespace App\Modules\VehicleVariantMaster\Models;

use App\Concerns\Auditable;
use App\Concerns\Searchable;
use App\Modules\FuelTypeMaster\Models\FuelTypeMaster;
use App\Modules\TransmissionTypeMaster\Models\TransmissionTypeMaster;
use App\Modules\VehicleModelMaster\Models\VehicleModelMaster;
use App\Modules\VehicleVariantMaster\Database\Factories\VehicleVariantMasterFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VehicleVariantMaster extends Model
{
    use Auditable;
    use HasFactory;
    use Searchable;

    protected $table = 'vehicle_variants';

    protected $guarded = [];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    protected static array $searchableFields = ['name'];

    public function model(): BelongsTo
    {
        return $this->belongsTo(VehicleModelMaster::class, 'model_id');
    }

    public function fuelType(): BelongsTo
    {
        return $this->belongsTo(FuelTypeMaster::class, 'fuel_type_id');
    }

    public function transmissionType(): BelongsTo
    {
        return $this->belongsTo(TransmissionTypeMaster::class, 'transmission_type_id');
    }

    protected static function newFactory(): VehicleVariantMasterFactory
    {
        return VehicleVariantMasterFactory::new();
    }
}
