<?php

namespace App\Modules\InspectionItemGroupMaster\Database\Seeders;

use App\Modules\InspectionItemGroupMaster\Models\InspectionItemGroupMaster;
use Illuminate\Database\Seeder;

class InspectionItemGroupMasterSeeder extends Seeder
{
    public function run(): void
    {
        // Real workshop inspection groups — what techs will see in PMS / Bodyshop / Tyre checklists
        $real = [
            ['name' => 'ENGINE',     'code' => 'ENG'],
            ['name' => 'BRAKE',      'code' => 'BRK'],
            ['name' => 'SUSPENSION', 'code' => 'SUS'],
            ['name' => 'BODY',       'code' => 'BDY'],
            ['name' => 'TYRE',       'code' => 'TYR'],
            ['name' => 'ELECTRICAL', 'code' => 'ELE'],
            ['name' => 'FLUID',      'code' => 'FLD'],
            ['name' => 'COOLING',    'code' => 'COL'],
            ['name' => 'AC-HEATER',  'code' => 'AC'],
        ];

        foreach ($real as $group) {
            InspectionItemGroupMaster::firstOrCreate(
                ['name' => $group['name']],
                ['code' => $group['code'], 'is_active' => true],
            );
        }
    }
}
