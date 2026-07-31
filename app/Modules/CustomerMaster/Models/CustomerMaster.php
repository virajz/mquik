<?php

namespace App\Modules\CustomerMaster\Models;

use App\Concerns\Auditable;
use App\Concerns\Searchable;
use App\Modules\Appointment\Models\Appointment;
use App\Modules\BusinessTypeMaster\Models\BusinessTypeMaster;
use App\Modules\CustomerMaster\Database\Factories\CustomerMasterFactory;
use App\Modules\CustomerVehicleMaster\Models\CustomerVehicleMaster;
use App\Modules\GstTypeMaster\Models\GstTypeMaster;
use App\Modules\PickupDrop\Models\PickupDrop;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\Storage;

class CustomerMaster extends Model
{
    use Auditable;
    use HasFactory;
    use Searchable;

    protected $table = 'customers';

    protected $guarded = [];

    public function businessType(): BelongsTo
    {
        return $this->belongsTo(BusinessTypeMaster::class, 'business_type_id');
    }

    public function gstType(): BelongsTo
    {
        return $this->belongsTo(GstTypeMaster::class, 'gst_type_id');
    }

    public function referredBy(): BelongsTo
    {
        return $this->belongsTo(self::class, 'referred_by_customer_id');
    }

    public function referrals(): HasMany
    {
        return $this->hasMany(self::class, 'referred_by_customer_id');
    }

    public function addresses(): HasMany
    {
        return $this->hasMany(CustomerAddress::class, 'customer_id');
    }

    public function primaryAddress(): HasOne
    {
        return $this->hasOne(CustomerAddress::class, 'customer_id')->where('is_primary', true);
    }

    public function vehicles(): HasMany
    {
        return $this->hasMany(CustomerVehicleMaster::class, 'customer_id');
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class, 'customer_id');
    }

    public function pickupDrops(): HasMany
    {
        return $this->hasMany(PickupDrop::class, 'customer_id');
    }

    protected $casts = [
        'date_of_birth' => 'date',
        'is_active' => 'boolean',
    ];

    protected static array $searchableFields = ['first_name', 'middle_name', 'last_name', 'phone', 'email', 'aadhar', 'pan', 'gstin'];

    /**
     * Virtual `name` attribute — joins first/middle/last for display.
     * Lets all callers continue to use $customer->name without changes.
     */
    public function getNameAttribute(): string
    {
        return collect([$this->first_name, $this->middle_name, $this->last_name])
            ->filter()
            ->implode(' ');
    }

    /**
     * Virtual `name` setter — accepts a single string and splits it across
     * first/middle/last using the same heuristic as the migration backfill,
     * so existing factories / seeders / imports that pass `name` keep working.
     */
    public function setNameAttribute(?string $value): void
    {
        $value = trim(preg_replace('/\s+/', ' ', (string) $value) ?? '');
        if ($value === '') {
            $this->attributes['first_name'] = null;
            $this->attributes['middle_name'] = null;
            $this->attributes['last_name'] = null;

            return;
        }

        $parts = explode(' ', $value);
        match (count($parts)) {
            1 => $this->fillNameParts($parts[0], null, null),
            2 => $this->fillNameParts($parts[0], null, $parts[1]),
            3 => $this->fillNameParts($parts[0], $parts[1], $parts[2]),
            default => $this->fillNameParts($value, null, null), // 4+ → assume company
        };
    }

    protected function fillNameParts(?string $first, ?string $middle, ?string $last): void
    {
        $this->attributes['first_name'] = $first;
        $this->attributes['middle_name'] = $middle;
        $this->attributes['last_name'] = $last;
    }

    public function toSearchResult(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->name,
            'subtitle' => $this->phone ? '+91 '.$this->phone : null,
        ];
    }

    protected static function newFactory(): CustomerMasterFactory
    {
        return CustomerMasterFactory::new();
    }

    /**
     * Wipe KYC files from storage when the customer record is deleted, so
     * orphaned blobs don't accumulate on S3 / disk.
     */
    protected static function booted(): void
    {
        static::deleting(function (self $customer) {
            foreach (['aadhar_file_path', 'pan_file_path', 'gst_certificate_file_path'] as $col) {
                if ($customer->{$col}) {
                    Storage::delete($customer->{$col});
                }
            }
        });
    }
}
