<?php

namespace App\Modules\VehicleSegmentMaster\Models;

use App\Concerns\Auditable;
use App\Concerns\Searchable;
use App\Modules\VehicleModelMaster\Models\VehicleModelMaster;
use App\Modules\VehicleSegmentMaster\Database\Factories\VehicleSegmentMasterFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VehicleSegmentMaster extends Model
{
    use Auditable;
    use HasFactory;
    use Searchable;

    protected $table = 'vehicle_segments';

    protected $guarded = [];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    protected static array $searchableFields = ['name', 'code'];

    public function models(): HasMany
    {
        return $this->hasMany(VehicleModelMaster::class, 'vehicle_segment_id');
    }

    protected static function newFactory(): VehicleSegmentMasterFactory
    {
        return VehicleSegmentMasterFactory::new();
    }
}
