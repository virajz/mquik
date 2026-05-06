<?php

namespace App\Modules\JobDescriptionMaster\Database\Seeders;

use App\Modules\JobDescriptionMaster\Models\JobDescriptionMaster;
use App\Modules\ServiceTypeMaster\Models\ServiceTypeMaster;
use Illuminate\Database\Seeder;

class JobDescriptionMasterSeeder extends Seeder
{
    public function run(): void
    {
        $byName = ServiceTypeMaster::query()->pluck('id', 'name');

        $resolve = fn (?string $serviceType) => $serviceType ? ($byName[strtoupper($serviceType)] ?? null) : null;

        $real = [
            // Frequent — daily PMS / repair tasks
            ['name' => 'ENGINE OIL CHANGE',          'code' => 'ENG-OIL', 'st' => 'PERIODIC MAINTENANCE', 'cat' => 'frequent', 'h' => 0.50],
            ['name' => 'OIL FILTER CHANGE',          'code' => 'OIL-FLT', 'st' => 'PERIODIC MAINTENANCE', 'cat' => 'frequent', 'h' => 0.25],
            ['name' => 'AIR FILTER CHANGE',          'code' => 'AIR-FLT', 'st' => 'PERIODIC MAINTENANCE', 'cat' => 'frequent', 'h' => 0.25],
            ['name' => 'CABIN FILTER CHANGE',        'code' => 'CAB-FLT', 'st' => 'PERIODIC MAINTENANCE', 'cat' => 'frequent', 'h' => 0.25],
            ['name' => 'BRAKE PAD REPLACEMENT',      'code' => 'BRK-PAD', 'st' => 'GENERAL REPAIR',       'cat' => 'frequent', 'h' => 1.00],
            ['name' => 'BRAKE FLUID TOP-UP',         'code' => 'BRK-FLD', 'st' => 'PERIODIC MAINTENANCE', 'cat' => 'frequent', 'h' => 0.25],
            ['name' => 'COOLANT TOP-UP',             'code' => 'COOL-TU', 'st' => 'PERIODIC MAINTENANCE', 'cat' => 'frequent', 'h' => 0.25],
            ['name' => 'BATTERY CHECK',              'code' => 'BAT-CHK', 'st' => 'PERIODIC MAINTENANCE', 'cat' => 'frequent', 'h' => 0.25],
            ['name' => 'WHEEL ALIGNMENT',            'code' => 'WHL-ALN', 'st' => 'WHEEL ALIGNMENT',      'cat' => 'frequent', 'h' => 0.50],
            ['name' => 'TYRE ROTATION',              'code' => 'TYR-ROT', 'st' => 'TYRE REPLACEMENT',     'cat' => 'frequent', 'h' => 0.50],

            // General — broader catalog
            ['name' => 'CLUTCH OVERHAUL',            'code' => 'CLU-OVH', 'st' => 'GENERAL REPAIR', 'cat' => 'general', 'h' => 4.00],
            ['name' => 'AC GAS REFILL',              'code' => 'AC-GAS',  'st' => 'GENERAL REPAIR', 'cat' => 'general', 'h' => 1.00],
            ['name' => 'AC COMPRESSOR REPLACEMENT',  'code' => 'AC-CMP',  'st' => 'GENERAL REPAIR', 'cat' => 'general', 'h' => 3.00],
            ['name' => 'TIMING BELT REPLACEMENT',    'code' => 'TIM-BLT', 'st' => 'GENERAL REPAIR', 'cat' => 'general', 'h' => 3.00],
            ['name' => 'SUSPENSION OVERHAUL',        'code' => 'SUS-OVH', 'st' => 'GENERAL REPAIR', 'cat' => 'general', 'h' => 4.00],
        ];

        foreach ($real as $row) {
            JobDescriptionMaster::firstOrCreate(
                ['name' => $row['name']],
                [
                    'code' => $row['code'],
                    'category' => $row['cat'],
                    'service_type_id' => $resolve($row['st']),
                    'standard_hours' => $row['h'],
                    'is_active' => true,
                ],
            );
        }
    }
}
