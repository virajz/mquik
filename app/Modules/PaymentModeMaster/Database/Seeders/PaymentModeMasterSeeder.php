<?php

namespace App\Modules\PaymentModeMaster\Database\Seeders;

use App\Modules\PaymentModeMaster\Models\PaymentModeMaster;
use Illuminate\Database\Seeder;

class PaymentModeMasterSeeder extends Seeder
{
    public function run(): void
    {
        // Common payment modes used across billing, receipts and procurement.
        $real = [
            ['name' => 'CASH',          'code' => 'CSH'],
            ['name' => 'UPI',           'code' => 'UPI'],
            ['name' => 'CARD',          'code' => 'CRD'],
            ['name' => 'BANK TRANSFER', 'code' => 'BNK'],
            ['name' => 'CHEQUE',        'code' => 'CHQ'],
            ['name' => 'CREDIT NOTE',   'code' => 'CRN'],
        ];

        foreach ($real as $row) {
            PaymentModeMaster::firstOrCreate(
                ['name' => $row['name']],
                $row + ['is_active' => true],
            );
        }
    }
}
