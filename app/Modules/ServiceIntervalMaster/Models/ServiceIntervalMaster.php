<?php

namespace App\Modules\ServiceIntervalMaster\Models;

use App\Concerns\Searchable;
use App\Modules\ServiceIntervalMaster\Database\Factories\ServiceIntervalMasterFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * How often one service is due — by time, by distance, or both.
 */
class ServiceIntervalMaster extends Model
{
    use HasFactory;
    use Searchable;

    protected $table = 'service_interval_masters';

    protected $guarded = [];

    protected $casts = [
        'interval_months' => 'integer',
        'interval_km' => 'integer',
        'is_active' => 'boolean',
    ];

    protected static array $searchableFields = ['name', 'description'];

    protected static function newFactory(): ServiceIntervalMasterFactory
    {
        return ServiceIntervalMasterFactory::new();
    }

    /** A service with no bound set can never be overdue. */
    public function hasBound(): bool
    {
        return $this->interval_months !== null || $this->interval_km !== null;
    }

    /** "6 months / 10,000 km" — however much of it is set. */
    public function label(): string
    {
        $parts = [];

        if ($this->interval_months !== null) {
            $parts[] = $this->interval_months.' '.str('month')->plural($this->interval_months);
        }

        if ($this->interval_km !== null) {
            $parts[] = number_format($this->interval_km).' km';
        }

        return $parts === [] ? 'No interval' : implode(' / ', $parts);
    }
}
