<?php

namespace App\Modules\LabourMaster\Database\Seeders;

use App\Modules\LabourMaster\Models\LabourMaster;
use Illuminate\Database\Seeder;

class LabourMasterSeeder extends Seeder
{
    public function run(): void
    {
        LabourMaster::factory()->count(10)->create();
    }
}
