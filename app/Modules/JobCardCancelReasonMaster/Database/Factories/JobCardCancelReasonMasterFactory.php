<?php

namespace App\Modules\JobCardCancelReasonMaster\Database\Factories;

use App\Modules\JobCardCancelReasonMaster\Models\JobCardCancelReasonMaster;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<JobCardCancelReasonMaster>
 */
class JobCardCancelReasonMasterFactory extends Factory
{
    protected $model = JobCardCancelReasonMaster::class;

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
