<?php

namespace App\Modules\Payroll\Database\Seeders;

use App\Modules\Payroll\Models\Payroll;
use Illuminate\Database\Seeder;

class PayrollSeeder extends Seeder
{
    public function run(): void
    {
        Payroll::factory()->count(10)->create();
    }
}
