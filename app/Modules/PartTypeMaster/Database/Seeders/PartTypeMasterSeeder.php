<?php

namespace App\Modules\PartTypeMaster\Database\Seeders;

use App\Modules\PartTypeMaster\Models\PartTypeMaster;
use Illuminate\Database\Seeder;

class PartTypeMasterSeeder extends Seeder
{
    public function run(): void
    {
        // Part sourcing types.
        $real = [
            ['name' => 'GENUINE',      'code' => 'GEN'],
            ['name' => 'AFTER MARKET', 'code' => 'AFT'],
            ['name' => 'OEM',          'code' => 'OEM'],
            ['name' => 'REFURBISHED',  'code' => 'RFB'],
        ];

        foreach ($real as $type) {
            PartTypeMaster::firstOrCreate(
                ['name' => $type['name']],
                ['code' => $type['code'], 'is_active' => true],
            );
        }
    }
}
