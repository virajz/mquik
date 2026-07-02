<?php

namespace App\Modules\ReceiptCancellationReasonMaster\Database\Seeders;

use App\Modules\ReceiptCancellationReasonMaster\Models\ReceiptCancellationReasonMaster;
use Illuminate\Database\Seeder;

class ReceiptCancellationReasonMasterSeeder extends Seeder
{
    public function run(): void
    {
        // Reasons a customer receipt is cancelled.
        $real = [
            ['name' => 'CHEQUE RETURN',   'code' => 'CHQR'],
            ['name' => 'DUPLICATE ENTRY', 'code' => 'DUP'],
            ['name' => 'WRONG CUSTOMER',  'code' => 'WCUST'],
            ['name' => 'WRONG AMOUNT',    'code' => 'WAMT'],
            ['name' => 'PAYMENT FAILED',  'code' => 'PFAIL'],
        ];

        foreach ($real as $type) {
            ReceiptCancellationReasonMaster::firstOrCreate(
                ['name' => $type['name']],
                ['code' => $type['code'], 'is_active' => true],
            );
        }
    }
}
