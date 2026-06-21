<?php

namespace App\Modules\InsurancePolicyTypeMaster\Database\Seeders;

use App\Modules\InsurancePolicyTypeMaster\Models\InsurancePolicyTypeMaster;
use Illuminate\Database\Seeder;

class InsurancePolicyTypeMasterSeeder extends Seeder
{
    public function run(): void
    {
        $real = [
            ['name' => 'COMPREHENSIVE', 'code' => 'COMP'],
            ['name' => 'THIRD PARTY',   'code' => 'TP'],
            ['name' => 'ZERO DEP',      'code' => 'ZD'],
            ['name' => 'CORPORATE',     'code' => 'CORP'],
        ];

        foreach ($real as $row) {
            InsurancePolicyTypeMaster::firstOrCreate(['name' => $row['name']], ['code' => $row['code'], 'is_active' => true]);
        }
    }
}
