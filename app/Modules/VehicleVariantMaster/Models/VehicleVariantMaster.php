<?php

namespace App\Modules\VehicleVariantMaster\Models;

use App\Concerns\Auditable;
use App\Concerns\Searchable;
use App\Modules\CustomerVehicleMaster\Models\CustomerVehicleMaster;
use App\Modules\FuelTypeMaster\Models\FuelTypeMaster;
use App\Modules\SpareMaster\Models\SpareMaster;
use App\Modules\TransmissionTypeMaster\Models\TransmissionTypeMaster;
use App\Modules\VehicleModelMaster\Models\VehicleModelMaster;
use App\Modules\VehicleVariantMaster\Database\Factories\VehicleVariantMasterFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VehicleVariantMaster extends Model
{
    use Auditable;
    use HasFactory;
    use Searchable;

    protected $table = 'vehicle_variants';

    protected $guarded = [];

    protected $casts = [
        'is_active' => 'boolean',
        'service_interval_km' => 'integer',
        'service_interval_months' => 'integer',
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

    public function customerVehicles(): HasMany
    {
        return $this->hasMany(CustomerVehicleMaster::class, 'variant_id');
    }

    protected static function newFactory(): VehicleVariantMasterFactory
    {
        return VehicleVariantMasterFactory::new();
    }

    /** Spares whose compatibility list includes this variant. */
    public function spares(): BelongsToMany
    {
        return $this->belongsToMany(
            SpareMaster::class,
            'spare_vehicle_variants',
            'vehicle_variant_id',
            'spare_id',
        )->withTimestamps();
    }

    /**
     * The service interval this variant actually uses: its own override if set,
     * otherwise the model's. Either figure can independently fall back.
     *
     * @return array{km:?int, months:?int}
     */
    public function effectiveServiceInterval(): array
    {
        return [
            'km' => $this->service_interval_km ?? $this->model?->service_interval_km,
            'months' => $this->service_interval_months ?? $this->model?->service_interval_months,
        ];
    }
}
