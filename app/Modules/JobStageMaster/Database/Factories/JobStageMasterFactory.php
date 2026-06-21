<?php

namespace App\Modules\JobStageMaster\Database\Factories;

use App\Modules\JobStageMaster\Models\JobStageMaster;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<JobStageMaster>
 */
class JobStageMasterFactory extends Factory
{
    protected $model = JobStageMaster::class;

    public function definition(): array
    {
        return [
            'name' => strtoupper($this->faker->unique()->words(2, true)),
            'code' => null,
            'track' => $this->faker->randomElement(['regular', 'insurance']),
            'sort_order' => 0,
            'is_active' => true,
            'notes' => null,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }

    public function inTrack(string $track, int $sortOrder = 0): static
    {
        return $this->state(fn () => ['track' => $track, 'sort_order' => $sortOrder]);
    }

    public function withCode(string $code): static
    {
        return $this->state(fn () => ['code' => strtoupper($code)]);
    }
}
