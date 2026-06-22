<?php

namespace App\Modules\BayMaster\Database\Seeders;

use App\Modules\BayMaster\Models\BayMaster;
use Illuminate\Database\Seeder;

class BayMasterSeeder extends Seeder
{
    public function run(): void
    {
        // Physical service bays / work stations on the workshop floor.
        $bays = [
            ['name' => 'BAY 1',         'code' => 'B1'],
            ['name' => 'BAY 2',         'code' => 'B2'],
            ['name' => 'BAY 3',         'code' => 'B3'],
            ['name' => 'WASH BAY',      'code' => 'WSH'],
            ['name' => 'PAINT BOOTH',   'code' => 'PNT'],
            ['name' => 'DENTING BAY',   'code' => 'DNT'],
            ['name' => 'ALIGNMENT BAY', 'code' => 'ALN'],
            ['name' => 'PMS BAY',       'code' => 'PMS'],
        ];

        foreach ($bays as $bay) {
            BayMaster::firstOrCreate(
                ['name' => $bay['name']],
                ['code' => $bay['code'], 'is_active' => true],
            );
        }
    }
}
