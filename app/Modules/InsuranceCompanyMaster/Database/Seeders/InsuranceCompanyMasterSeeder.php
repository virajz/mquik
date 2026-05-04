<?php

namespace App\Modules\InsuranceCompanyMaster\Database\Seeders;

use App\Modules\InsuranceCompanyMaster\Models\InsuranceCompanyMaster;
use Illuminate\Database\Seeder;

class InsuranceCompanyMasterSeeder extends Seeder
{
    public function run(): void
    {
        InsuranceCompanyMaster::factory()->count(10)->create();
    }
}
