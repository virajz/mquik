<?php

namespace App\Modules\TimeSlotMaster\Models;

use App\Concerns\Auditable;
use App\Concerns\Searchable;
use App\Modules\TimeSlotMaster\Database\Factories\TimeSlotMasterFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TimeSlotMaster extends Model
{
    use Auditable;
    use HasFactory;
    use Searchable;

    protected $table = 'time_slots';

    protected $guarded = [];

    protected $casts = [
        'is_active' => 'boolean',
        'max_vehicles_per_slot' => 'integer',
        'buffer_minutes' => 'integer',
    ];

    protected static array $searchableFields = ['name', 'code'];

    protected static function newFactory(): TimeSlotMasterFactory
    {
        return TimeSlotMasterFactory::new();
    }

    /** "09:00 – 10:00" — the label used in appointment dropdowns and reports. */
    public function window(): string
    {
        return $this->formatTime($this->slot_start_time).' – '.$this->formatTime($this->slot_end_time);
    }

    /**
     * Times come back as "H:i:s" strings on Postgres and SQLite alike; trim to H:i
     * without paying for a Carbon parse on every dropdown row.
     */
    protected function formatTime(?string $value): string
    {
        return $value === null ? '' : substr($value, 0, 5);
    }
}
