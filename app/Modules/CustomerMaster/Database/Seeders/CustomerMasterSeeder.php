<?php

namespace App\Modules\CustomerMaster\Database\Seeders;

use App\Modules\CustomerMaster\Models\CustomerMaster;
use Illuminate\Database\Seeder;

class CustomerMasterSeeder extends Seeder
{
    public function run(): void
    {
        // 5 walking, 3 loyal, 2 corporate — matches typical workshop customer mix
        CustomerMaster::factory()->count(5)->create();
        CustomerMaster::factory()->count(3)->loyal()->create();
        CustomerMaster::factory()->count(2)->corporate()->create();
    }
}
