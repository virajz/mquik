<?php

namespace App\Modules\CustomerMaster\Database\Seeders;

use App\Modules\CustomerMaster\Models\CustomerMaster;
use Faker\Factory;
use Illuminate\Database\Seeder;

class CustomerMasterSeeder extends Seeder
{
    public function run(): void
    {
        // Sample rows need Faker (dev-only). On a --no-dev server deploy Faker is
        // absent, so skip — real customers come from MasterDataSeeder.
        if (! class_exists(Factory::class)) {
            return;
        }

        // 5 walking, 3 loyal, 2 corporate — matches typical workshop customer mix
        CustomerMaster::factory()->count(5)->create();
        CustomerMaster::factory()->count(3)->loyal()->create();
        CustomerMaster::factory()->count(2)->corporate()->create();
    }
}
