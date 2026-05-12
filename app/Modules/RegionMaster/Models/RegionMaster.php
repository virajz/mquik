<?php

namespace App\Modules\RegionMaster\Models;

use App\Concerns\Auditable;
use App\Concerns\Searchable;
use App\Modules\RegionMaster\Database\Factories\RegionMasterFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RegionMaster extends Model
{
    use Auditable;
    use HasFactory;
    use Searchable;

    protected $table = 'regions';

    protected $guarded = [];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    protected static array $searchableFields = ['name', 'code'];

    /**
     * @return array<int, string>
     */
    public static function kinds(): array
    {
        return ['state', 'city', 'area', 'pincode'];
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    protected static function newFactory(): RegionMasterFactory
    {
        return RegionMasterFactory::new();
    }
}
