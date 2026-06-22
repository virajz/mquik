<?php

namespace App\Modules\EstimateTemplateMaster\Database\Factories;

use App\Modules\EstimateTemplateMaster\Models\EstimateTemplateMaster;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EstimateTemplateMaster>
 */
class EstimateTemplateMasterFactory extends Factory
{
    protected $model = EstimateTemplateMaster::class;

    public function definition(): array
    {
        return [
            'name' => strtoupper($this->faker->unique()->words(2, true)),
            'code' => null,
            'is_active' => true,
            'notes' => null,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }
}
