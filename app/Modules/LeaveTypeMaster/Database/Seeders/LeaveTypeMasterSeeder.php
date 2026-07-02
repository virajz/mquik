<?php

namespace App\Modules\LeaveTypeMaster\Database\Seeders;

use App\Modules\LeaveTypeMaster\Models\LeaveTypeMaster;
use Illuminate\Database\Seeder;

class LeaveTypeMasterSeeder extends Seeder
{
    public function run(): void
    {
        // Employee leave types used by leave management.
        $real = [
            ['name' => 'CASUAL LEAVE',     'code' => 'CL'],
            ['name' => 'SICK LEAVE',       'code' => 'SL'],
            ['name' => 'EARNED LEAVE',     'code' => 'EL'],
            ['name' => 'MATERNITY LEAVE',  'code' => 'ML'],
            ['name' => 'LEAVE WITHOUT PAY', 'code' => 'LWP'],
        ];

        foreach ($real as $type) {
            LeaveTypeMaster::firstOrCreate(
                ['name' => $type['name']],
                ['code' => $type['code'], 'is_active' => true],
            );
        }
    }
}
