<?php

namespace App\Modules\JobCardPendingReasonMaster\Database\Factories;

use App\Modules\JobCardPendingReasonMaster\Models\JobCardPendingReasonMaster;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<JobCardPendingReasonMaster>
 */
class JobCardPendingReasonMasterFactory extends Factory
{
    protected $model = JobCardPendingReasonMaster::class;

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

    public function withCode(string $code): static
    {
        return $this->state(fn () => ['code' => strtoupper($code)]);
    }
}
