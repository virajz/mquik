<?php

namespace App\Modules\PendingReasonMaster\Database\Seeders;

use App\Modules\PendingReasonMaster\Models\PendingReasonMaster;
use Illuminate\Database\Seeder;

class PendingReasonMasterSeeder extends Seeder
{
    public function run(): void
    {
        // Why an appointment is still pending — advisor follow-up queue.
        $real = [
            ['name' => 'CUSTOMER REQUEST', 'code' => 'CREQ'],
            ['name' => 'TRAFFIC ISSUE',    'code' => 'TRAF'],
        ];

        foreach ($real as $row) {
            PendingReasonMaster::firstOrCreate(
                ['name' => $row['name']],
                ['code' => $row['code'], 'is_active' => true],
            );
        }
    }
}
