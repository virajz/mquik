<?php

namespace App\Modules\ReceiptDifferenceReasonMaster\Database\Seeders;

use App\Modules\ReceiptDifferenceReasonMaster\Models\ReceiptDifferenceReasonMaster;
use Illuminate\Database\Seeder;

class ReceiptDifferenceReasonMasterSeeder extends Seeder
{
    public function run(): void
    {
        // Reasons the received amount differs from the expected amount.
        $real = [
            ['name' => 'SHORT PAYMENT',          'code' => 'SHORT'],
            ['name' => 'BANK CHARGES DEDUCTED',  'code' => 'BNKCHG'],
            ['name' => 'SETTLEMENT DIFFERENCE',  'code' => 'SETTLE'],
            ['name' => 'EXCESS PAYMENT',         'code' => 'EXCESS'],
        ];

        foreach ($real as $type) {
            ReceiptDifferenceReasonMaster::firstOrCreate(
                ['name' => $type['name']],
                ['code' => $type['code'], 'is_active' => true],
            );
        }
    }
}
