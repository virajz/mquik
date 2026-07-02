<?php

namespace App\Modules\InvoiceCancellationReasonMaster\Database\Seeders;

use App\Modules\InvoiceCancellationReasonMaster\Models\InvoiceCancellationReasonMaster;
use Illuminate\Database\Seeder;

class InvoiceCancellationReasonMasterSeeder extends Seeder
{
    public function run(): void
    {
        // Reasons a regular / counter sales invoice or credit note is cancelled.
        $real = [
            ['name' => 'DUPLICATE INVOICE',   'code' => 'DUP'],
            ['name' => 'WRONG TAX',           'code' => 'TAX'],
            ['name' => 'WRONG CUSTOMER',      'code' => 'CUST'],
            ['name' => 'PRICING ERROR',       'code' => 'PRICE'],
            ['name' => 'CUSTOMER REQUEST',    'code' => 'REQ'],
            ['name' => 'ORDER CANCELLED',     'code' => 'CANC'],
            ['name' => 'GOODS RETURNED',      'code' => 'RET'],
        ];

        foreach ($real as $reason) {
            InvoiceCancellationReasonMaster::firstOrCreate(
                ['name' => $reason['name']],
                ['code' => $reason['code'], 'is_active' => true],
            );
        }
    }
}
