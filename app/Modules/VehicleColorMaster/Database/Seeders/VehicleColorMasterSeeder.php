<?php

namespace App\Modules\VehicleColorMaster\Database\Seeders;

use App\Modules\VehicleColorMaster\Models\VehicleColorMaster;
use Illuminate\Database\Seeder;

class VehicleColorMasterSeeder extends Seeder
{
    public function run(): void
    {
        $real = [
            ['name' => 'PEARL WHITE', 'hex_code' => '#F8F8F8'],
            ['name' => 'METALLIC BLACK', 'hex_code' => '#1A1A1A'],
            ['name' => 'METALLIC SILVER', 'hex_code' => '#C0C0C0'],
            ['name' => 'METALLIC RED', 'hex_code' => '#B00020'],
            ['name' => 'STEEL GREY', 'hex_code' => '#5C5C5C'],
            ['name' => 'OCEAN BLUE', 'hex_code' => '#0070C0'],
            ['name' => 'CHAMPAGNE GOLD', 'hex_code' => '#D4AF37'],
            ['name' => 'BURGUNDY', 'hex_code' => '#800020'],
        ];
        foreach ($real as $color) {
            VehicleColorMaster::firstOrCreate(['name' => $color['name']], $color + ['is_active' => true]);
        }
    }
}
