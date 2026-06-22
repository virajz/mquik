<?php

namespace App\Modules\ChallanRejectionReasonMaster\Database\Seeders;

use App\Modules\ChallanRejectionReasonMaster\Models\ChallanRejectionReasonMaster;
use Illuminate\Database\Seeder;

class ChallanRejectionReasonMasterSeeder extends Seeder
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
            ChallanRejectionReasonMaster::firstOrCreate(
                ['name' => $type['name']],
                ['code' => $type['code'], 'is_active' => true],
            );
        }
    }
}
