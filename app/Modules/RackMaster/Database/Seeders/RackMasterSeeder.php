<?php

namespace App\Modules\RackMaster\Database\Seeders;

use App\Modules\RackMaster\Models\RackMaster;
use Illuminate\Database\Seeder;

class RackMasterSeeder extends Seeder
{
    public function run(): void
    {
        // Physical storage racks / bins in the parts store.
        $real = [
            ['name' => 'A1', 'code' => 'A1'],
            ['name' => 'A2', 'code' => 'A2'],
            ['name' => 'A3', 'code' => 'A3'],
            ['name' => 'B1', 'code' => 'B1'],
            ['name' => 'B2', 'code' => 'B2'],
            ['name' => 'B3', 'code' => 'B3'],
            ['name' => 'C1', 'code' => 'C1'],
            ['name' => 'C2', 'code' => 'C2'],
        ];

        foreach ($real as $type) {
            RackMaster::firstOrCreate(
                ['name' => $type['name']],
                ['code' => $type['code'], 'is_active' => true],
            );
        }
    }
}
