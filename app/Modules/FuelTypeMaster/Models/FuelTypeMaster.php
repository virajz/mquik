<?php

namespace App\Modules\FuelTypeMaster\Models;

use App\Concerns\Auditable;
use App\Concerns\Searchable;
use App\Modules\FuelTypeMaster\Database\Factories\FuelTypeMasterFactory;
use App\Modules\VehicleVariantMaster\Models\VehicleVariantMaster;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FuelTypeMaster extends Model
{
    use Auditable;
    use HasFactory;
    use Searchable;

    protected $table = 'fuel_types';

    protected $guarded = [];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    protected static array $searchableFields = ['name', 'code'];

    public function variants(): HasMany
    {
        return $this->hasMany(VehicleVariantMaster::class, 'fuel_type_id');
    }

    protected static function newFactory(): FuelTypeMasterFactory
    {
        return FuelTypeMasterFactory::new();
    }
}
