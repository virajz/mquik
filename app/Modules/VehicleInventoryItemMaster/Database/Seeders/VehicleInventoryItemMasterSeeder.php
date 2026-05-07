<?php

namespace App\Modules\VehicleInventoryItemMaster\Database\Seeders;

use App\Modules\VehicleInventoryItemMaster\Models\VehicleInventoryItemMaster;
use Illuminate\Database\Seeder;

class VehicleInventoryItemMasterSeeder extends Seeder
{
    public function run(): void
    {
        // Real items the advisor ticks off at vehicle intake.
        $real = [
            ['name' => 'SPARE WHEEL', 'code' => 'SW'],
            ['name' => 'JACK', 'code' => 'JCK'],
            ['name' => 'TOOL KIT', 'code' => 'TK'],
            ['name' => 'FIRE EXTINGUISHER', 'code' => 'FE'],
            ['name' => 'FLOOR MATS', 'code' => 'FM'],
            ['name' => 'MUSIC SYSTEM', 'code' => 'MS'],
            ['name' => "OWNER'S MANUAL", 'code' => 'OM'],
            ['name' => 'RC COPY', 'code' => 'RC'],
            ['name' => 'INSURANCE PAPERS', 'code' => 'INS'],
            ['name' => 'PUC CERTIFICATE', 'code' => 'PUC'],
            ['name' => 'FUEL LEVEL', 'code' => 'FUEL'],
            ['name' => 'ODOMETER READING', 'code' => 'ODO'],
            ['name' => 'GPS DEVICE', 'code' => 'GPS'],
            ['name' => 'ANTI-RUST KIT', 'code' => 'ARK'],
            ['name' => 'MUD FLAPS', 'code' => 'MFL'],
            ['name' => 'DASHBOARD CAMERA', 'code' => 'DC'],
            ['name' => 'SEAT COVERS', 'code' => 'SC'],
            ['name' => 'SUNFILM', 'code' => 'SF'],
        ];

        foreach ($real as $item) {
            VehicleInventoryItemMaster::firstOrCreate(
                ['name' => $item['name']],
                ['code' => $item['code'], 'is_active' => true],
            );
        }
    }
}
