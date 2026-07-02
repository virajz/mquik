<?php

namespace App\Modules\ConsumableCategoryMaster\Database\Seeders;

use App\Modules\ConsumableCategoryMaster\Models\ConsumableCategoryMaster;
use Illuminate\Database\Seeder;

class ConsumableCategoryMasterSeeder extends Seeder
{
    public function run(): void
    {
        // Real complaint categories used by the workshop CRM and advisor routing.
        $real = [
            ['name' => 'PAINT SHOP CONSUMABLES', 'code' => 'PNT'],
            ['name' => 'WORKSHOP CONSUMABLES',   'code' => 'WRK'],
            ['name' => 'WASHING CONSUMABLES',    'code' => 'WSH'],
            ['name' => 'DETAILING CONSUMABLES',  'code' => 'DTL'],
            ['name' => 'GENERAL CONSUMABLES',    'code' => 'GEN'],
        ];

        foreach ($real as $type) {
            ConsumableCategoryMaster::firstOrCreate(
                ['name' => $type['name']],
                ['code' => $type['code'], 'is_active' => true],
            );
        }
    }
}
