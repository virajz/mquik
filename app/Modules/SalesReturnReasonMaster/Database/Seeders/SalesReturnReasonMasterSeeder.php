<?php

namespace App\Modules\SalesReturnReasonMaster\Database\Seeders;

use App\Modules\SalesReturnReasonMaster\Models\SalesReturnReasonMaster;
use Illuminate\Database\Seeder;

class SalesReturnReasonMasterSeeder extends Seeder
{
    public function run(): void
    {
        // Reasons goods are returned across regular / insurance / counter sales.
        $real = [
            ['name' => 'MANUFACTURING DEFECT', 'code' => 'MFG'],
            ['name' => 'DAMAGED MATERIAL',     'code' => 'DMG'],
            ['name' => 'WRONG BILLING NAME',   'code' => 'WBN'],
            ['name' => 'WRONG RATE',           'code' => 'WRT'],
            ['name' => 'WRONG QUANTITY',       'code' => 'WQT'],
            ['name' => 'WRONG GST',            'code' => 'WGST'],
            ['name' => 'PRODUCT FAILURE',      'code' => 'PFL'],
            ['name' => 'POOR QUALITY',         'code' => 'PQL'],
            ['name' => 'WARRANTY CLAIM',       'code' => 'WCL'],
            ['name' => 'DUPLICATE BILLING',    'code' => 'DUP'],
        ];

        foreach ($real as $reason) {
            SalesReturnReasonMaster::firstOrCreate(
                ['name' => $reason['name']],
                ['code' => $reason['code'], 'is_active' => true],
            );
        }
    }
}
