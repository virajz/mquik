<?php

namespace App\Modules\DamageTypeMaster\Database\Seeders;

use App\Modules\DamageTypeMaster\Models\DamageTypeMaster;
use Illuminate\Database\Seeder;

class DamageTypeMasterSeeder extends Seeder
{
    public function run(): void
    {
        // Standard damage classifications used to tag photos and missing/damaged inventory.
        $real = [
            ['name' => 'SCRATCH',    'code' => 'SCR'],
            ['name' => 'DENT',       'code' => 'DNT'],
            ['name' => 'CRACK',      'code' => 'CRK'],
            ['name' => 'RUST',       'code' => 'RST'],
            ['name' => 'BROKEN',     'code' => 'BRK'],
            ['name' => 'PAINT FADE', 'code' => 'FAD'],
            ['name' => 'CHIP',       'code' => 'CHP'],
            ['name' => 'MISSING',    'code' => 'MIS'],
        ];

        foreach ($real as $type) {
            DamageTypeMaster::firstOrCreate(
                ['name' => $type['name']],
                ['code' => $type['code'], 'is_active' => true],
            );
        }
    }
}
