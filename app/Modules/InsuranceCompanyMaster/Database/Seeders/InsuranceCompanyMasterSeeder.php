<?php

namespace App\Modules\InsuranceCompanyMaster\Database\Seeders;

use App\Modules\InsuranceCompanyMaster\Models\InsuranceCompanyMaster;
use Faker\Factory;
use Illuminate\Database\Seeder;

class InsuranceCompanyMasterSeeder extends Seeder
{
    public function run(): void
    {
        // Sample rows need Faker (dev-only). On a --no-dev server deploy Faker is
        // absent, so skip — real insurers come from MasterDataSeeder.
        if (! class_exists(Factory::class)) {
            return;
        }

        InsuranceCompanyMaster::factory()->count(10)->create();
    }
}
