<?php

namespace App\Modules\ConsumableDepartmentMaster\Database\Seeders;

use App\Modules\ConsumableDepartmentMaster\Models\ConsumableDepartmentMaster;
use Illuminate\Database\Seeder;

class ConsumableDepartmentMasterSeeder extends Seeder
{
    public function run(): void
    {
        // Real cost-centres consumables get allocated to.
        $real = [
            ['name' => 'SERVICE BAY',     'code' => 'SBY'],
            ['name' => 'BODYSHOP BOOTH',  'code' => 'BSB'],
            ['name' => 'TYRE BAY',        'code' => 'TYB'],
            ['name' => 'DETAILING ROOM',  'code' => 'DTR'],
            ['name' => 'OFFICE',          'code' => 'OFF'],
            ['name' => 'STORES',          'code' => 'STR'],
        ];

        foreach ($real as $row) {
            ConsumableDepartmentMaster::firstOrCreate(
                ['name' => $row['name']],
                ['code' => $row['code'], 'is_active' => true],
            );
        }
    }
}
