<?php

namespace App\Modules\TransmissionTypeMaster\Models;

use App\Concerns\Auditable;
use App\Concerns\Searchable;
use App\Modules\TransmissionTypeMaster\Database\Factories\TransmissionTypeMasterFactory;
use App\Modules\VehicleVariantMaster\Models\VehicleVariantMaster;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TransmissionTypeMaster extends Model
{
    use Auditable;
    use HasFactory;
    use Searchable;

    protected $table = 'transmission_types';

    protected $guarded = [];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    protected static array $searchableFields = ['name', 'code'];

    public function variants(): HasMany
    {
        return $this->hasMany(VehicleVariantMaster::class, 'transmission_type_id');
    }

    protected static function newFactory(): TransmissionTypeMasterFactory
    {
        return TransmissionTypeMasterFactory::new();
    }
}
