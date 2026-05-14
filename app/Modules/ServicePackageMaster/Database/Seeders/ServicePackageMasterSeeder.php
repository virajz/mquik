<?php

namespace App\Modules\ServicePackageMaster\Database\Seeders;

use App\Modules\ServicePackageMaster\Models\ServicePackageMaster;
use Illuminate\Database\Seeder;

class ServicePackageMasterSeeder extends Seeder
{
    public function run(): void
    {
        ServicePackageMaster::factory()->count(10)->create();
    }
}
