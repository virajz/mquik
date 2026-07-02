<?php

namespace App\Modules\HolidayMaster\Models;

use App\Concerns\Auditable;
use App\Concerns\Searchable;
use App\Modules\HolidayMaster\Database\Factories\HolidayMasterFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HolidayMaster extends Model
{
    use Auditable;
    use HasFactory;
    use Searchable;

    protected $table = 'holidays';

    protected $guarded = [];

    protected $casts = [
        'holiday_date' => 'date',
        'is_recurring' => 'boolean',
        'is_active' => 'boolean',
    ];

    protected static array $searchableFields = ['name', 'code'];

    protected static function newFactory(): HolidayMasterFactory
    {
        return HolidayMasterFactory::new();
    }

    /** @return array<string, string> */
    public static function holidayTypes(): array
    {
        return [
            'weekly_off' => 'Weekly Off',
            'national' => 'National Holiday',
            'festival' => 'Festival Holiday',
            'company' => 'Company Holiday',
        ];
    }
}
