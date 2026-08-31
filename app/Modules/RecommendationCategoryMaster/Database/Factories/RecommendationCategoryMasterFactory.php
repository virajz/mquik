<?php

namespace App\Modules\RecommendationCategoryMaster\Database\Factories;

use App\Modules\RecommendationCategoryMaster\Models\RecommendationCategoryMaster;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RecommendationCategoryMaster>
 */
class RecommendationCategoryMasterFactory extends Factory
{
    protected $model = RecommendationCategoryMaster::class;

    public function definition(): array
    {
        return [
            'name' => strtoupper($this->faker->unique()->words(2, true)),
            'is_active' => true,
            'sequence_no' => 0,
        ];
    }

    /** A Sub Category filed under a Category. */
    public function under(RecommendationCategoryMaster|int $parent): static
    {
        return $this->state(fn () => [
            'parent_id' => $parent instanceof RecommendationCategoryMaster ? $parent->id : $parent,
        ]);
    }
}
