<?php

namespace App\Modules\ItemRejectionReasonMaster\Database\Seeders;

use App\Modules\ItemRejectionReasonMaster\Models\ItemRejectionReasonMaster;
use Illuminate\Database\Seeder;

class ItemRejectionReasonMasterSeeder extends Seeder
{
    public function run(): void
    {
        // Real complaint categories used by the workshop CRM and advisor routing.
        $real = [
            ['name' => 'DAMAGED',         'code' => 'DMG'],
            ['name' => 'WRONG PART',      'code' => 'WRP'],
            ['name' => 'EXCESS QUANTITY', 'code' => 'EXQ'],
            ['name' => 'QUALITY ISSUE',   'code' => 'QLT'],
            ['name' => 'EXPIRED',         'code' => 'EXP'],
            ['name' => 'RATE DIFFERENCE', 'code' => 'RTD'],
        ];

        foreach ($real as $type) {
            ItemRejectionReasonMaster::firstOrCreate(
                ['name' => $type['name']],
                ['code' => $type['code'], 'is_active' => true],
            );
        }
    }
}
