<?php

namespace App\Modules\CustomerVehicleMaster\Models;

use App\Concerns\Auditable;
use App\Concerns\Searchable;
use App\Modules\Appointment\Models\Appointment;
use App\Modules\CustomerMaster\Models\CustomerMaster;
use App\Modules\CustomerVehicleMaster\Database\Factories\CustomerVehicleMasterFactory;
use App\Modules\JobCard\Models\JobCard;
use App\Modules\PickupDrop\Models\PickupDrop;
use App\Modules\RegistrationTypeMaster\Models\RegistrationTypeMaster;
use App\Modules\VehicleColorMaster\Models\VehicleColorMaster;
use App\Modules\VehicleModelMaster\Models\VehicleModelMaster;
use App\Modules\VehicleVariantMaster\Models\VehicleVariantMaster;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CustomerVehicleMaster extends Model
{
    use Auditable;
    use HasFactory;
    use Searchable;

    protected $table = 'customer_vehicles';

    protected $guarded = [];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    protected static array $searchableFields = ['registration_no', 'vin', 'engine_no'];

    /** Plate type for a vehicle that has no registration number yet. */
    public const PLATE_UNREGISTERED = 'UNREGISTERED';

    public function isUnregistered(): bool
    {
        return mb_strtoupper((string) $this->registrationType?->name) === self::PLATE_UNREGISTERED;
    }

    /** What to print where a plate is expected. */
    public function plateLabel(): string
    {
        return $this->registration_no ?: ($this->isUnregistered() ? 'Unregistered' : '—');
    }

    public function toSearchResult(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->registration_no ?? '#'.$this->id,
            'subtitle' => trim(($this->model?->brand?->name ?? '').' '.($this->model?->name ?? '')) ?: null,
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(CustomerMaster::class, 'customer_id');
    }

    public function model(): BelongsTo
    {
        return $this->belongsTo(VehicleModelMaster::class, 'model_id');
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(VehicleVariantMaster::class, 'variant_id');
    }

    public function color(): BelongsTo
    {
        return $this->belongsTo(VehicleColorMaster::class, 'color_id');
    }

    public function registrationType(): BelongsTo
    {
        return $this->belongsTo(RegistrationTypeMaster::class, 'registration_type_id');
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class, 'customer_vehicle_id');
    }

    public function pickupDrops(): HasMany
    {
        return $this->hasMany(PickupDrop::class, 'customer_vehicle_id');
    }

    public function jobCards(): HasMany
    {
        return $this->hasMany(JobCard::class, 'customer_vehicle_id');
    }

    protected static function newFactory(): CustomerVehicleMasterFactory
    {
        return CustomerVehicleMasterFactory::new();
    }
}
