<?php

namespace App\Modules\ChecklistGroupMaster\Database\Seeders;

use App\Modules\ChecklistGroupMaster\Models\ChecklistGroupMaster;
use Illuminate\Database\Seeder;

class ChecklistGroupMasterSeeder extends Seeder
{
    public function run(): void
    {
        // Real workshop checklist categories — distinct from vehicle inspection groups.
        $real = [
            ['name' => 'DOCUMENT COLLECTION', 'code' => 'DOC'],
            ['name' => 'PRE-DELIVERY', 'code' => 'PD'],
            ['name' => 'SAFETY', 'code' => 'SAF'],
            ['name' => 'QUALITY', 'code' => 'QC'],
            ['name' => 'INSURANCE CLAIM', 'code' => 'INS'],
            ['name' => 'JOB CARD CLOSURE', 'code' => 'JCC'],
            ['name' => 'GATE PASS', 'code' => 'GP'],
            ['name' => 'BODYSHOP HANDOVER', 'code' => 'BSH'],
        ];

        foreach ($real as $group) {
            ChecklistGroupMaster::firstOrCreate(
                ['name' => $group['name']],
                ['code' => $group['code'], 'is_active' => true],
            );
        }
    }
}
