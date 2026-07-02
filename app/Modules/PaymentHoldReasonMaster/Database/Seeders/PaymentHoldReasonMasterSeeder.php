<?php

namespace App\Modules\PaymentHoldReasonMaster\Database\Seeders;

use App\Modules\PaymentHoldReasonMaster\Models\PaymentHoldReasonMaster;
use Illuminate\Database\Seeder;

class PaymentHoldReasonMasterSeeder extends Seeder
{
    public function run(): void
    {
        // Reasons a vendor payment is held back before release.
        $real = [
            ['name' => 'INVOICE DISPUTE',  'code' => 'DISP'],
            ['name' => 'QUALITY ISSUE',    'code' => 'QUAL'],
            ['name' => 'MANAGEMENT HOLD',  'code' => 'MGMT'],
        ];

        foreach ($real as $type) {
            PaymentHoldReasonMaster::firstOrCreate(
                ['name' => $type['name']],
                ['code' => $type['code'], 'is_active' => true],
            );
        }
    }
}
