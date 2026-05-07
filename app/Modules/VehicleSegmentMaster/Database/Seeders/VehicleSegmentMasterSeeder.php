<?php

namespace App\Modules\VehicleSegmentMaster\Database\Seeders;

use App\Modules\VehicleSegmentMaster\Models\VehicleSegmentMaster;
use Illuminate\Database\Seeder;

class VehicleSegmentMasterSeeder extends Seeder
{
    public function run(): void
    {
        $segments = [
            ['name' => 'HATCHBACK',   'code' => 'HCH'],
            ['name' => 'SEDAN',       'code' => 'SDN'],
            ['name' => 'SUV',         'code' => 'SUV'],
            ['name' => 'MUV',         'code' => 'MUV'],
            ['name' => 'COUPE',       'code' => 'CPE'],
            ['name' => 'CONVERTIBLE', 'code' => 'CNV'],
            ['name' => 'PICKUP',      'code' => 'PKP'],
            ['name' => 'COMMERCIAL',  'code' => 'CMR'],
            ['name' => 'BIKE',        'code' => 'BIK'],
            ['name' => 'SCOOTER',     'code' => 'SCT'],
        ];

        foreach ($segments as $segment) {
            VehicleSegmentMaster::firstOrCreate(
                ['name' => $segment['name']],
                ['code' => $segment['code'], 'is_active' => true],
            );
        }
    }
}
