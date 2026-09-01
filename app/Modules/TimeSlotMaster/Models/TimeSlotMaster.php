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

    /** "09:00 AM – 10:00 AM" — the label used in appointment dropdowns and reports. */
    public function window(): string
    {
        return $this->formatTime($this->slot_start_time).' – '.$this->formatTime($this->slot_end_time);
    }

    /**
     * Times come back as "H:i:s" strings on Postgres and SQLite alike. Displayed
     * as h:i A so slots read the same way as every other time in the app — a
     * 24-hour window next to a 12-hour appointment made one screen carry two
     * clocks. Formatted by hand rather than through Carbon, which would parse on
     * every dropdown row.
     */
    protected function formatTime(?string $value): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        [$hour, $minute] = array_pad(explode(':', $value), 2, '00');
        $hour = (int) $hour;

        return sprintf('%02d:%s %s', $hour % 12 === 0 ? 12 : $hour % 12, substr($minute, 0, 2), $hour < 12 ? 'AM' : 'PM');
    }
}
