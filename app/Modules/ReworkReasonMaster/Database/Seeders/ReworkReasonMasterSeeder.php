<?php

namespace App\Modules\ReworkReasonMaster\Database\Seeders;

use App\Modules\ReworkReasonMaster\Models\ReworkReasonMaster;
use Illuminate\Database\Seeder;

class ReworkReasonMasterSeeder extends Seeder
{
    public function run(): void
    {
        // Reasons a job is sent back for rework.
        $real = [
            ['name' => 'PARTS POOR QUALITY',          'code' => 'PPQ'],
            ['name' => 'LABOUR POOR QUALITY',         'code' => 'LPQ'],
            ['name' => 'INCOMPLETE WORK',             'code' => 'INC'],
            ['name' => 'MISSED TASK',                 'code' => 'MIS'],
            ['name' => 'CUSTOMER COMPLAINT',          'code' => 'CMP'],
            ['name' => 'SUPERVISOR INSTRUCTION',      'code' => 'SUP'],
        ];

        foreach ($real as $type) {
            ReworkReasonMaster::firstOrCreate(
                ['name' => $type['name']],
                ['code' => $type['code'], 'is_active' => true],
            );
        }
    }
}
