<?php

namespace App\Modules\RefundTypeMaster\Database\Seeders;

use App\Modules\RefundTypeMaster\Models\RefundTypeMaster;
use Illuminate\Database\Seeder;

class RefundTypeMasterSeeder extends Seeder
{
    public function run(): void
    {
        // Types of customer refund handled by the receipt refund workflow.
        $real = [
            ['name' => 'ADVANCE AMT / SECURITY DEPOSIT', 'code' => 'ADV'],
            ['name' => 'EXCESS PAYMENT',                 'code' => 'EXCESS'],
            ['name' => 'DUPLICATE PAYMENT',              'code' => 'DUP'],
            ['name' => 'ORDER CANCEL',                   'code' => 'CANC'],
            ['name' => 'SALES RETURN',                   'code' => 'SRET'],
            ['name' => 'COMPENSATION REFUND',            'code' => 'COMP'],
        ];

        foreach ($real as $type) {
            RefundTypeMaster::firstOrCreate(
                ['name' => $type['name']],
                ['code' => $type['code'], 'is_active' => true],
            );
        }
    }
}
