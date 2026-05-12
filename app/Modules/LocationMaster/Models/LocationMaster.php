<?php

namespace App\Modules\LocationMaster\Models;

use App\Concerns\Auditable;
use App\Concerns\Searchable;
use App\Modules\LocationMaster\Database\Factories\LocationMasterFactory;
use App\Modules\RegionMaster\Models\RegionMaster;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LocationMaster extends Model
{
    use Auditable;
    use HasFactory;
    use Searchable;

    protected $table = 'locations';

    protected $guarded = [];

    protected $casts = [
        'is_head_office' => 'boolean',
        'is_active' => 'boolean',
    ];

    protected static array $searchableFields = ['name', 'code', 'gstin'];

    public function city(): BelongsTo
    {
        return $this->belongsTo(RegionMaster::class, 'city_id');
    }

    public function state(): BelongsTo
    {
        return $this->belongsTo(RegionMaster::class, 'state_id');
    }

    protected static function newFactory(): LocationMasterFactory
    {
        return LocationMasterFactory::new();
    }
}
