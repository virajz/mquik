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

    protected static array $searchableFields = [
        'registration_no', 'vin', 'engine_no',
        'customer.first_name', 'customer.last_name', 'customer.phone',
        'model.name', 'model.brand.name',
    ];

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

    /**
     * The name the workshop actually says out loud, e.g.
     * "LAND ROVER VOGUE 3.0 LWB (DSL) AT" — brand, model, variant, then fuel
     * and transmission abbreviated the way a plate-side conversation uses them.
     *
     * @param  bool  $withBrand  false drops the brand where the context already implies it
     */
    public function fullName(bool $withBrand = true): string
    {
        $parts = [
            $withBrand ? $this->model?->brand?->name : null,
            $this->model?->name,
            $this->variant?->name,
        ];

        $name = trim(implode(' ', array_filter($parts)));

        if ($fuel = self::abbreviateFuel($this->variant?->fuelType?->name)) {
            $name .= ' ('.$fuel.')';
        }

        if ($transmission = self::abbreviateTransmission($this->variant?->transmissionType?->name)) {
            $name .= ' '.$transmission;
        }

        return $name;
    }

    /** DIESEL → DSL, PETROL → PTL … anything unknown keeps its own first three letters. */
    public static function abbreviateFuel(?string $fuel): ?string
    {
        if (! $fuel) {
            return null;
        }

        $fuel = mb_strtoupper(trim($fuel));

        return match (true) {
            str_contains($fuel, 'DIESEL') => 'DSL',
            str_contains($fuel, 'PETROL') => 'PTL',
            str_contains($fuel, 'CNG') => 'CNG',
            str_contains($fuel, 'LPG') => 'LPG',
            str_contains($fuel, 'ELECTRIC') => 'EV',
            str_contains($fuel, 'HYBRID') => 'HYB',
            default => mb_substr($fuel, 0, 3),
        };
    }

    /** AUTOMATIC → AT, MANUAL → MT, and the gearbox acronyms as they are. */
    public static function abbreviateTransmission(?string $transmission): ?string
    {
        if (! $transmission) {
            return null;
        }

        $transmission = mb_strtoupper(trim($transmission));

        return match (true) {
            str_contains($transmission, 'AMT') => 'AMT',
            str_contains($transmission, 'CVT') => 'CVT',
            str_contains($transmission, 'DCT') => 'DCT',
            str_contains($transmission, 'AUTO') => 'AT',
            str_contains($transmission, 'MANUAL') => 'MT',
            default => mb_substr($transmission, 0, 3),
        };
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
