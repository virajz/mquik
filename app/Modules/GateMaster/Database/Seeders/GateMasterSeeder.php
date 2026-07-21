<?php

namespace App\Modules\GateMaster\Database\Seeders;

use App\Modules\GateMaster\Models\GateMaster;
use Illuminate\Database\Seeder;

class GateMasterSeeder extends Seeder
{
    public function run(): void
    {
        // Entry/exit gates the security desk logs vehicles through.
        $real = [
            ['name' => 'GATE NO. 1', 'code' => 'G1'],
            ['name' => 'GATE NO. 2', 'code' => 'G2'],
        ];

        foreach ($real as $row) {
            GateMaster::firstOrCreate(
                ['name' => $row['name']],
                ['code' => $row['code'], 'is_active' => true],
            );
        }
    }
}
