<?php

namespace App\Modules\TimeSlotMaster\Database\Seeders;

use App\Modules\TimeSlotMaster\Models\TimeSlotMaster;
use Illuminate\Database\Seeder;

class TimeSlotMasterSeeder extends Seeder
{
    public function run(): void
    {
        // A standard workshop day, one-hour intake windows with a 13:00 lunch break.
        $real = [
            ['name' => '09:00-10:00', 'code' => 'S1', 'start' => '09:00:00', 'end' => '10:00:00'],
            ['name' => '10:00-11:00', 'code' => 'S2', 'start' => '10:00:00', 'end' => '11:00:00'],
            ['name' => '11:00-12:00', 'code' => 'S3', 'start' => '11:00:00', 'end' => '12:00:00'],
            ['name' => '12:00-13:00', 'code' => 'S4', 'start' => '12:00:00', 'end' => '13:00:00'],
            ['name' => '14:00-15:00', 'code' => 'S5', 'start' => '14:00:00', 'end' => '15:00:00'],
            ['name' => '15:00-16:00', 'code' => 'S6', 'start' => '15:00:00', 'end' => '16:00:00'],
            ['name' => '16:00-17:00', 'code' => 'S7', 'start' => '16:00:00', 'end' => '17:00:00'],
        ];

        foreach ($real as $slot) {
            TimeSlotMaster::firstOrCreate(
                ['name' => $slot['name']],
                [
                    'code' => $slot['code'],
                    'slot_start_time' => $slot['start'],
                    'slot_end_time' => $slot['end'],
                    'max_vehicles_per_slot' => 5,
                    'buffer_minutes' => 0,
                    'is_active' => true,
                ],
            );
        }
    }
}
