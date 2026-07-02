<?php

namespace App\Modules\CreditNoteReasonMaster\Database\Seeders;

use App\Modules\CreditNoteReasonMaster\Models\CreditNoteReasonMaster;
use Illuminate\Database\Seeder;

class CreditNoteReasonMasterSeeder extends Seeder
{
    public function run(): void
    {
        // Real complaint categories used by the workshop CRM and advisor routing.
        $real = [
            ['name' => 'EXCESS QTY',              'code' => 'EXQ'],
            ['name' => 'WRONG MATERIAL ORDER',    'code' => 'WMO'],
            ['name' => 'WRONG MATERIAL SUPPLIED', 'code' => 'WMS'],
            ['name' => 'POOR QUALITY',            'code' => 'PQL'],
            ['name' => 'DAMAGE',                  'code' => 'DMG'],
            ['name' => 'DEFECTIVE',               'code' => 'DEF'],
            ['name' => 'WRONG RATE',              'code' => 'WRT'],
            ['name' => 'WRONG DISCOUNT',          'code' => 'WDS'],
            ['name' => 'CREDIT ADJUSTMENT',       'code' => 'CADJ'],
            ['name' => 'TECHNICAL FAILURE',       'code' => 'TFL'],
        ];

        foreach ($real as $type) {
            CreditNoteReasonMaster::firstOrCreate(
                ['name' => $type['name']],
                ['code' => $type['code'], 'is_active' => true],
            );
        }
    }
}
