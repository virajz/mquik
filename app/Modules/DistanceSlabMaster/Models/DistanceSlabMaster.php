<?php

namespace App\Modules\DistanceSlabMaster\Models;

use App\Concerns\Auditable;
use App\Concerns\Searchable;
use App\Modules\DistanceSlabMaster\Database\Factories\DistanceSlabMasterFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DistanceSlabMaster extends Model
{
    use Auditable;
    use HasFactory;
    use Searchable;

    protected $table = 'distance_slabs';

    protected $guarded = [];

    protected $casts = [
        'is_active' => 'boolean',
        'min_km' => 'integer',
        'max_km' => 'integer',
        'charge_amount' => 'decimal:2',
    ];

    protected static array $searchableFields = ['name', 'code'];

    protected static function newFactory(): DistanceSlabMasterFactory
    {
        return DistanceSlabMasterFactory::new();
    }

    /** "0–5 KM" / "26 KM & above" — the label shown in dropdowns and reports. */
    public function band(): string
    {
        return $this->max_km === null
            ? $this->min_km.' KM & above'
            : $this->min_km.'–'.$this->max_km.' KM';
    }

    /**
     * The slab a given distance falls into, or null when nothing covers it.
     * Open-ended top slabs (null max_km) match anything at or above min_km.
     */
    public static function forDistance(float $km): ?self
    {
        return static::query()
            ->where('is_active', true)
            ->where('min_km', '<=', $km)
            ->where(fn ($q) => $q->whereNull('max_km')->orWhere('max_km', '>=', $km))
            ->orderBy('min_km')
            ->first();
    }
}
