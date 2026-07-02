<?php

namespace App\Modules\EmployeeCategoryMaster\Database\Seeders;

use App\Modules\EmployeeCategoryMaster\Models\EmployeeCategoryMaster;
use Illuminate\Database\Seeder;

class EmployeeCategoryMasterSeeder extends Seeder
{
    public function run(): void
    {
        // Employment categories for workshop staff.
        $real = [
            ['name' => 'PERMANENT',  'code' => 'PERM'],
            ['name' => 'PROBATION',  'code' => 'PROB'],
            ['name' => 'APPRENTICE', 'code' => 'APPR'],
        ];

        foreach ($real as $type) {
            EmployeeCategoryMaster::firstOrCreate(
                ['name' => $type['name']],
                ['code' => $type['code'], 'is_active' => true],
            );
        }
    }
}
