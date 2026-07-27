<?php

namespace App\Modules\IpiRejectionReasonMaster\Database\Seeders;

use App\Modules\IpiRejectionReasonMaster\Models\IpiRejectionReasonMaster;
use Illuminate\Database\Seeder;

class IpiRejectionReasonMasterSeeder extends Seeder
{
    public function run(): void
    {
        // Why the store rejects an internal parts inquiry.
        $real = [
            ['name' => 'NOT IN STOCK',          'code' => 'NIS'],
            ['name' => 'OBSOLETE PART',         'code' => 'OBS'],
            ['name' => 'WRONG PART REQUESTED',  'code' => 'WPR'],
            ['name' => 'BUDGET ISSUE',          'code' => 'BUD'],
        ];

        foreach ($real as $type) {
            IpiRejectionReasonMaster::firstOrCreate(
                ['name' => $type['name']],
                ['code' => $type['code'], 'is_active' => true],
            );
        }
    }
}
