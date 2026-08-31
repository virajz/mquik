<?php

namespace App\Modules\RecommendationDescriptionMaster\Database\Factories;

use App\Modules\RecommendationCategoryMaster\Models\RecommendationCategoryMaster;
use App\Modules\RecommendationDescriptionMaster\Models\RecommendationDescriptionMaster;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RecommendationDescriptionMaster>
 */
class RecommendationDescriptionMasterFactory extends Factory
{
    protected $model = RecommendationDescriptionMaster::class;

    public function definition(): array
    {
        return [
            'name' => strtoupper($this->faker->unique()->words(3, true)),
            'category_id' => RecommendationCategoryMaster::factory(),
            'is_active' => true,
            'sequence_no' => 0,
        ];
    }
}
