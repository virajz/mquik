<?php

namespace App\Modules\DigitalInspection\Database\Seeders;

use App\Modules\DigitalInspection\Models\DigitalInspection;
use Illuminate\Database\Seeder;

class DigitalInspectionSeeder extends Seeder
{
    public function run(): void
    {
        DigitalInspection::factory()->count(10)->create();
    }
}
