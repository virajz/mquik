<?php

namespace App\Modules\CompanyMaster\Models;

use App\Concerns\Auditable;
use App\Concerns\Searchable;
use App\Modules\CompanyMaster\Database\Factories\CompanyMasterFactory;
use App\Modules\RegionMaster\Models\RegionMaster;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CompanyMaster extends Model
{
    use Auditable;
    use HasFactory;
    use Searchable;

    protected $table = 'companies';

    protected $guarded = [];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /** @var array<int, string> */
    protected static array $searchableFields = ['legal_name', 'trade_name', 'gstin'];

    public function city(): BelongsTo
    {
        return $this->belongsTo(RegionMaster::class, 'city_id');
    }

    public function state(): BelongsTo
    {
        return $this->belongsTo(RegionMaster::class, 'state_id');
    }

    public function toSearchResult(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->legal_name,
            'subtitle' => $this->trade_name.($this->gstin ? ' • '.$this->gstin : ''),
        ];
    }

    /**
     * Singleton accessor: there is only ever one company row (the workshop's own legal entity).
     * Returns null when the workshop has not yet filled it in.
     */
    public static function instance(): ?self
    {
        return static::query()->first();
    }

    protected static function newFactory(): CompanyMasterFactory
    {
        return CompanyMasterFactory::new();
    }
}
