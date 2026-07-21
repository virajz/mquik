<?php

namespace App\Modules\CancelReasonMaster\Database\Seeders;

use App\Modules\CancelReasonMaster\Models\CancelReasonMaster;
use Illuminate\Database\Seeder;

class CancelReasonMasterSeeder extends Seeder
{
    public function run(): void
    {
        // Why an appointment was cancelled — CRM cancellation analysis.
        $real = [
            ['name' => 'CUSTOMER NOT AVAILABLE', 'code' => 'CNA'],
            ['name' => 'WRONG ADDRESS',           'code' => 'ADDR'],
            ['name' => 'VEHICLE NOT READY',       'code' => 'VNR'],
            ['name' => 'DRIVER UNAVAILABLE',      'code' => 'DRV'],
        ];

        foreach ($real as $row) {
            CancelReasonMaster::firstOrCreate(
                ['name' => $row['name']],
                ['code' => $row['code'], 'is_active' => true],
            );
        }
    }
}
