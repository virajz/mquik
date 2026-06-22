<?php

namespace App\Modules\WorkOrderHoldReasonMaster\Database\Seeders;

use App\Modules\WorkOrderHoldReasonMaster\Models\WorkOrderHoldReasonMaster;
use Illuminate\Database\Seeder;

class WorkOrderHoldReasonMasterSeeder extends Seeder
{
    public function run(): void
    {
        // Reasons a technician pauses / holds a work order mid-job.
        $real = [
            ['name' => 'WAITING FOR PARTS',         'code' => 'WFP'],
            ['name' => 'WAITING FOR ADVISOR',       'code' => 'WFA'],
            ['name' => 'WAITING FOR CUSTOMER APPROVAL', 'code' => 'WCA'],
            ['name' => 'TECHNICIAN NOT AVAILABLE',  'code' => 'TNA'],
            ['name' => 'EXTERNAL WORK PENDING',     'code' => 'EWP'],
            ['name' => 'LUNCH BREAK',               'code' => 'LNH'],
            ['name' => 'TEA BREAK',                 'code' => 'TEA'],
            ['name' => 'SYSTEM HOLD',               'code' => 'SYS'],
        ];

        foreach ($real as $type) {
            WorkOrderHoldReasonMaster::firstOrCreate(
                ['name' => $type['name']],
                ['code' => $type['code'], 'is_active' => true],
            );
        }
    }
}
