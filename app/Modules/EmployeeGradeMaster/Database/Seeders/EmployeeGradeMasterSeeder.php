<?php

namespace App\Modules\EmployeeGradeMaster\Database\Seeders;

use App\Modules\EmployeeGradeMaster\Models\EmployeeGradeMaster;
use Illuminate\Database\Seeder;

class EmployeeGradeMasterSeeder extends Seeder
{
    public function run(): void
    {
        // Staff pay / seniority grades.
        $real = [
            ['name' => 'GRADE A', 'code' => 'A'],
            ['name' => 'GRADE B', 'code' => 'B'],
            ['name' => 'GRADE C', 'code' => 'C'],
        ];

        foreach ($real as $type) {
            EmployeeGradeMaster::firstOrCreate(
                ['name' => $type['name']],
                ['code' => $type['code'], 'is_active' => true],
            );
        }
    }
}
