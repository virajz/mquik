<?php

namespace App\Modules\RecommendationCategoryMaster\Database\Seeders;

use App\Modules\RecommendationCategoryMaster\Models\RecommendationCategoryMaster;
use Illuminate\Database\Seeder;

class RecommendationCategoryMasterSeeder extends Seeder
{
    public function run(): void
    {
        RecommendationCategoryMaster::factory()->count(10)->create();
    }
}
