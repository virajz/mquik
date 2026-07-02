<?php

namespace App\Modules\PaymentCancellationReasonMaster\Database\Seeders;

use App\Modules\PaymentCancellationReasonMaster\Models\PaymentCancellationReasonMaster;
use Illuminate\Database\Seeder;

class PaymentCancellationReasonMasterSeeder extends Seeder
{
    public function run(): void
    {
        // Reasons a vendor payment is cancelled.
        $real = [
            ['name' => 'CHEQUE RETURN',   'code' => 'CHQR'],
            ['name' => 'DUPLICATE ENTRY', 'code' => 'DUP'],
            ['name' => 'WRONG VENDOR',    'code' => 'WVND'],
            ['name' => 'WRONG AMOUNT',    'code' => 'WAMT'],
            ['name' => 'PAYMENT FAILED',  'code' => 'PFAIL'],
        ];

        foreach ($real as $type) {
            PaymentCancellationReasonMaster::firstOrCreate(
                ['name' => $type['name']],
                ['code' => $type['code'], 'is_active' => true],
            );
        }
    }
}
