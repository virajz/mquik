<?php

namespace App\Modules\InspectionItemMaster\Database\Seeders;

use App\Modules\InspectionItemGroupMaster\Models\InspectionItemGroupMaster;
use App\Modules\InspectionItemMaster\Models\InspectionItemMaster;
use Illuminate\Database\Seeder;

class InspectionItemMasterSeeder extends Seeder
{
    public function run(): void
    {
        // Real workshop checkpoints — these are the items techs tick off in PMS / Bodyshop / Tyre.
        // Seeded as: [group_name, name, code, check_type, measurement_unit?]
        $real = [
            // ENGINE
            ['ENGINE',     'ENGINE OIL LEVEL',      'EOL', 'measurement', 'mm'],
            ['ENGINE',     'ENGINE OIL CONDITION',  'EOC', 'visual',      null],
            ['ENGINE',     'COOLANT LEVEL',         'COL', 'measurement', '%'],
            ['ENGINE',     'COOLANT CONDITION',     'CCN', 'visual',      null],

            // BRAKE
            ['BRAKE',      'BRAKE PAD THICKNESS',   'BPT', 'measurement', 'mm'],
            ['BRAKE',      'BRAKE FLUID LEVEL',     'BFL', 'visual',      null],
            ['BRAKE',      'BRAKE DISC THICKNESS',  'BDT', 'measurement', 'mm'],

            // TYRE
            ['TYRE',       'TYRE TREAD DEPTH',      'TTD', 'measurement', 'mm'],
            ['TYRE',       'TYRE PRESSURE FRONT',   'TPF', 'measurement', 'bar'],
            ['TYRE',       'TYRE PRESSURE REAR',    'TPR', 'measurement', 'bar'],

            // BODY
            ['BODY',       'SCRATCH PRESENT',       'SCR', 'yes_no',      null],
            ['BODY',       'DENT PRESENT',          'DNT', 'yes_no',      null],
            ['BODY',       'RUST PRESENT',          'RST', 'yes_no',      null],

            // ELECTRICAL
            ['ELECTRICAL', 'HEADLIGHT WORKING',     'HDL', 'yes_no',      null],
            ['ELECTRICAL', 'BATTERY VOLTAGE',       'BVT', 'measurement', 'V'],
        ];

        foreach ($real as [$groupName, $name, $code, $checkType, $unit]) {
            $group = InspectionItemGroupMaster::firstOrCreate(
                ['name' => $groupName],
                ['is_active' => true],
            );

            InspectionItemMaster::firstOrCreate(
                [
                    'inspection_item_group_id' => $group->id,
                    'name' => $name,
                ],
                [
                    'code' => $code,
                    'check_type' => $checkType,
                    'measurement_unit' => $unit,
                    'is_active' => true,
                ],
            );
        }
    }
}
