<?php

namespace App\Modules\ParkingSlotMaster\Database\Seeders;

use App\Modules\ParkingSlotMaster\Models\ParkingSlotMaster;
use Illuminate\Database\Seeder;

class ParkingSlotMasterSeeder extends Seeder
{
    public function run(): void
    {
        // Where a vehicle waits on site while it is with the workshop.
        $real = [
            ['name' => 'SLOT NO. 1',      'code' => 'P1'],
            ['name' => 'SLOT NO. 2',      'code' => 'P2'],
            ['name' => 'SLOT NO. 3',      'code' => 'P3'],
            ['name' => 'OUTSIDE OF GATE', 'code' => 'OUT'],
        ];

        foreach ($real as $row) {
            ParkingSlotMaster::firstOrCreate(
                ['name' => $row['name']],
                ['code' => $row['code'], 'is_active' => true],
            );
        }
    }
}
