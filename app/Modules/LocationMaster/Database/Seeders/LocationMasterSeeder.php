<?php

namespace App\Modules\LocationMaster\Database\Seeders;

use App\Modules\LocationMaster\Models\LocationMaster;
use App\Modules\RegionMaster\Models\RegionMaster;
use Illuminate\Database\Seeder;

class LocationMasterSeeder extends Seeder
{
    public function run(): void
    {
        // Resolve (or create) the FK targets gracefully — RegionMaster may or may not be seeded.
        $gujarat = RegionMaster::firstOrCreate(
            ['kind' => 'state', 'parent_id' => null, 'name' => 'GUJARAT'],
            ['code' => 'GJ', 'is_active' => true],
        );

        $ahmedabad = RegionMaster::firstOrCreate(
            ['kind' => 'city', 'parent_id' => $gujarat->id, 'name' => 'AHMEDABAD'],
            ['code' => 'AMD', 'is_active' => true],
        );

        $locations = [
            [
                'name' => 'SATELLITE BRANCH',
                'code' => 'SAT',
                'is_head_office' => true,
                'address' => 'BLOCK A, ISCON CROSS ROAD',
                'city_id' => $ahmedabad->id,
                'state_id' => $gujarat->id,
                'pincode' => '380015',
                'phone' => '9876543210',
                'email' => null,
                'gstin' => '24ABCDE1234F1Z5',
                'is_active' => true,
            ],
            [
                'name' => 'SG HIGHWAY BRANCH',
                'code' => 'SGH',
                'is_head_office' => false,
                'address' => null,
                'city_id' => $ahmedabad->id,
                'state_id' => $gujarat->id,
                'pincode' => '380054',
                'phone' => null,
                'email' => null,
                'gstin' => null,
                'is_active' => true,
            ],
        ];

        foreach ($locations as $location) {
            LocationMaster::firstOrCreate(['code' => $location['code']], $location);
        }
    }
}
