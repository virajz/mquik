<?php

namespace App\Modules\VehicleBrandMaster\Database\Seeders;

use App\Modules\VehicleBrandMaster\Models\VehicleBrandMaster;
use Illuminate\Database\Seeder;

class VehicleBrandMasterSeeder extends Seeder
{
    public function run(): void
    {
        $real = [
            ['name' => 'MARUTI SUZUKI', 'code' => 'MAR', 'country' => 'INDIA'],
            ['name' => 'HYUNDAI', 'code' => 'HYU', 'country' => 'SOUTH KOREA'],
            ['name' => 'TATA', 'code' => 'TAT', 'country' => 'INDIA'],
            ['name' => 'MAHINDRA', 'code' => 'MAH', 'country' => 'INDIA'],
            ['name' => 'HONDA', 'code' => 'HON', 'country' => 'JAPAN'],
            ['name' => 'TOYOTA', 'code' => 'TOY', 'country' => 'JAPAN'],
            ['name' => 'KIA', 'code' => 'KIA', 'country' => 'SOUTH KOREA'],
            ['name' => 'VOLKSWAGEN', 'code' => 'VW', 'country' => 'GERMANY'],
            ['name' => 'SKODA', 'code' => 'SKO', 'country' => 'CZECH REPUBLIC'],
            ['name' => 'RENAULT', 'code' => 'REN', 'country' => 'FRANCE'],
        ];

        foreach ($real as $brand) {
            VehicleBrandMaster::firstOrCreate(['name' => $brand['name']], $brand + ['is_active' => true]);
        }
    }
}
