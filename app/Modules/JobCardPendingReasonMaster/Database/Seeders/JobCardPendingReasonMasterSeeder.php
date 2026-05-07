<?php

namespace App\Modules\JobCardPendingReasonMaster\Database\Seeders;

use App\Modules\JobCardPendingReasonMaster\Models\JobCardPendingReasonMaster;
use Illuminate\Database\Seeder;

class JobCardPendingReasonMasterSeeder extends Seeder
{
    public function run(): void
    {
        $real = [
            ['name' => 'SPARE AWAITED',              'code' => 'SPR'],
            ['name' => 'ESTIMATE PENDING',           'code' => 'EST'],
            ['name' => 'CUSTOMER APPROVAL PENDING',  'code' => 'CAP'],
            ['name' => 'INSURANCE APPROVAL PENDING', 'code' => 'IAP'],
            ['name' => 'OUTSIDE WORK PENDING',       'code' => 'OWP'],
            ['name' => 'PAYMENT PENDING',            'code' => 'PMT'],
            ['name' => 'PARTS NOT AVAILABLE',        'code' => 'PNA'],
        ];

        foreach ($real as $row) {
            JobCardPendingReasonMaster::firstOrCreate(
                ['name' => $row['name']],
                ['code' => $row['code'], 'is_active' => true],
            );
        }
    }
}
