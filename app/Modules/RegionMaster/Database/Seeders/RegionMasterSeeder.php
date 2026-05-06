<?php

namespace App\Modules\RegionMaster\Database\Seeders;

use App\Modules\RegionMaster\Models\RegionMaster;
use Illuminate\Database\Seeder;

class RegionMasterSeeder extends Seeder
{
    public function run(): void
    {
        // Test hierarchy: Gujarat → Ahmedabad → Satellite → 380015
        $gj = RegionMaster::firstOrCreate(
            ['kind' => 'state', 'parent_id' => null, 'name' => 'GUJARAT'],
            ['code' => 'GJ', 'is_active' => true],
        );

        $ahd = RegionMaster::firstOrCreate(
            ['kind' => 'city', 'parent_id' => $gj->id, 'name' => 'AHMEDABAD'],
            ['code' => 'AHD', 'is_active' => true],
        );

        $sat = RegionMaster::firstOrCreate(
            ['kind' => 'area', 'parent_id' => $ahd->id, 'name' => 'SATELLITE'],
            ['is_active' => true],
        );

        RegionMaster::firstOrCreate(
            ['kind' => 'pincode', 'parent_id' => $sat->id, 'name' => '380015'],
            ['is_active' => true],
        );

        // A few more states without children — for picker realism.
        $extraStates = [
            ['name' => 'MAHARASHTRA', 'code' => 'MH'],
            ['name' => 'KARNATAKA',   'code' => 'KA'],
            ['name' => 'DELHI',       'code' => 'DL'],
            ['name' => 'RAJASTHAN',   'code' => 'RJ'],
        ];

        foreach ($extraStates as $s) {
            RegionMaster::firstOrCreate(
                ['kind' => 'state', 'parent_id' => null, 'name' => $s['name']],
                ['code' => $s['code'], 'is_active' => true],
            );
        }
    }
}
