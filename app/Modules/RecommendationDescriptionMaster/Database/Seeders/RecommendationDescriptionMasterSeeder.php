<?php

namespace App\Modules\RecommendationDescriptionMaster\Database\Seeders;

use App\Modules\RecommendationDescriptionMaster\Models\RecommendationDescriptionMaster;
use Illuminate\Database\Seeder;

class RecommendationDescriptionMasterSeeder extends Seeder
{
    public function run(): void
    {
        RecommendationDescriptionMaster::factory()->count(10)->create();
    }
}
