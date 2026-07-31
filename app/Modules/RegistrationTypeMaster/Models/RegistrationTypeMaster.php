<?php

namespace App\Modules\RegistrationTypeMaster\Models;

use App\Concerns\Auditable;
use App\Concerns\Searchable;
use App\Modules\CustomerVehicleMaster\Models\CustomerVehicleMaster;
use App\Modules\RegistrationTypeMaster\Database\Factories\RegistrationTypeMasterFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RegistrationTypeMaster extends Model
{
    use Auditable;
    use HasFactory;
    use Searchable;

    protected $table = 'registration_types';

    protected $guarded = [];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    protected static array $searchableFields = ['name', 'code'];

    public function customerVehicles(): HasMany
    {
        return $this->hasMany(CustomerVehicleMaster::class, 'registration_type_id');
    }

    protected static function newFactory(): RegistrationTypeMasterFactory
    {
        return RegistrationTypeMasterFactory::new();
    }
}
