<?php

namespace App\Modules\InventoryGroupMaster\Database\Seeders;

use App\Modules\InventoryGroupMaster\Models\InventoryGroupMaster;
use Illuminate\Database\Seeder;

class InventoryGroupMasterSeeder extends Seeder
{
    public function run(): void
    {
        // Real top-level inventory groups used by the spare and labour catalogs.
        $real = [
            ['name' => 'ENGINE',       'code' => 'ENG'],
            ['name' => 'BRAKE',        'code' => 'BRK'],
            ['name' => 'SUSPENSION',   'code' => 'SUS'],
            ['name' => 'FILTERS',      'code' => 'FLT'],
            ['name' => 'ELECTRICAL',   'code' => 'ELE'],
            ['name' => 'BODY',         'code' => 'BDY'],
            ['name' => 'COOLING',      'code' => 'COL'],
            ['name' => 'FUEL',         'code' => 'FUL'],
            ['name' => 'TRANSMISSION', 'code' => 'TRN'],
            ['name' => 'EXHAUST',      'code' => 'EXH'],
        ];

        foreach ($real as $row) {
            InventoryGroupMaster::firstOrCreate(
                ['name' => $row['name']],
                ['code' => $row['code'], 'parent_id' => null, 'is_active' => true],
            );
        }
    }
}
