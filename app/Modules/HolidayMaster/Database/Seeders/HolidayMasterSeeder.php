<?php

namespace App\Modules\HolidayMaster\Database\Seeders;

use App\Modules\HolidayMaster\Models\HolidayMaster;
use Illuminate\Database\Seeder;

class HolidayMasterSeeder extends Seeder
{
    public function run(): void
    {
        $real = [
            ['name' => 'SUNDAY',           'code' => 'SUN',  'holiday_type' => 'weekly_off', 'holiday_date' => null,         'is_recurring' => true],
            ['name' => 'REPUBLIC DAY',     'code' => 'RD',   'holiday_type' => 'national',   'holiday_date' => '2026-01-26', 'is_recurring' => true],
            ['name' => 'INDEPENDENCE DAY', 'code' => 'ID',   'holiday_type' => 'national',   'holiday_date' => '2026-08-15', 'is_recurring' => true],
            ['name' => 'DIWALI',           'code' => 'DIW',  'holiday_type' => 'festival',   'holiday_date' => null,         'is_recurring' => false],
            ['name' => 'FOUNDATION DAY',   'code' => 'FND',  'holiday_type' => 'company',    'holiday_date' => null,         'is_recurring' => false],
        ];

        foreach ($real as $holiday) {
            HolidayMaster::firstOrCreate(
                ['name' => $holiday['name']],
                [
                    'code' => $holiday['code'],
                    'holiday_type' => $holiday['holiday_type'],
                    'holiday_date' => $holiday['holiday_date'],
                    'is_recurring' => $holiday['is_recurring'],
                    'is_active' => true,
                ],
            );
        }
    }
}
