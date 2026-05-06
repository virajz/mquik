<?php

namespace App\Modules\JobDescriptionMaster\Database\Factories;

use App\Modules\JobDescriptionMaster\Models\JobDescriptionMaster;
use App\Modules\ServiceTypeMaster\Models\ServiceTypeMaster;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<JobDescriptionMaster>
 */
class JobDescriptionMasterFactory extends Factory
{
    protected $model = JobDescriptionMaster::class;

    public function definition(): array
    {
        return [
            'name' => strtoupper($this->faker->unique()->words(3, true)),
            'code' => null,
            'category' => 'general',
            'service_type_id' => ServiceTypeMaster::factory(),
            'standard_hours' => $this->faker->randomFloat(2, 0.25, 8),
            'is_active' => true,
            'notes' => null,
        ];
    }

    public function frequent(): static
    {
        return $this->state(fn () => ['category' => 'frequent']);
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }

    public function withoutServiceType(): static
    {
        return $this->state(fn () => ['service_type_id' => null]);
    }
}
