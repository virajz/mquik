<?php

namespace App\Modules\SpareMaster\Database\Seeders;

use App\Modules\SpareMaster\Models\SpareMaster;
use Illuminate\Database\Seeder;

class SpareMasterSeeder extends Seeder
{
    public function run(): void
    {
        SpareMaster::factory()->count(10)->create();
    }
}
