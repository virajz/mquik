<?php

namespace App\Modules\SmartSalary\Database\Seeders;

use App\Modules\SmartSalary\Models\SmartSalary;
use Illuminate\Database\Seeder;

class SmartSalarySeeder extends Seeder
{
    public function run(): void
    {
        SmartSalary::factory()->count(10)->create();
    }
}
