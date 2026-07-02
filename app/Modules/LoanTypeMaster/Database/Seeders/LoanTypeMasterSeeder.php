<?php

namespace App\Modules\LoanTypeMaster\Database\Seeders;

use App\Modules\LoanTypeMaster\Models\LoanTypeMaster;
use Illuminate\Database\Seeder;

class LoanTypeMasterSeeder extends Seeder
{
    public function run(): void
    {
        // Employee loan / advance types.
        $real = [
            ['name' => 'ADVANCE SALARY', 'code' => 'ADV'],
            ['name' => 'EMERGENCY LOAN', 'code' => 'EMRG'],
            ['name' => 'FESTIVAL ADVANCE', 'code' => 'FEST'],
        ];

        foreach ($real as $type) {
            LoanTypeMaster::firstOrCreate(
                ['name' => $type['name']],
                ['code' => $type['code'], 'is_active' => true],
            );
        }
    }
}
