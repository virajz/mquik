<?php

namespace App\Modules\ServiceIntervalMaster\Database\Factories;

use App\Modules\ServiceIntervalMaster\Models\ServiceIntervalMaster;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ServiceIntervalMaster>
 */
class ServiceIntervalMasterFactory extends Factory
{
    protected $model = ServiceIntervalMaster::class;

    public function definition(): array
    {
        return [
            // Names are unique in the table, so keep them collision-free.
            'name' => strtoupper($this->faker->unique()->words(2, true)),
            'interval_months' => $this->faker->randomElement([3, 6, 12, 24]),
            'interval_km' => $this->faker->randomElement([5000, 10000, 20000, 40000]),
            'description' => null,
            'is_active' => true,
        ];
    }

    /** A cosmetic job that is never overdue. */
    public function neverDue(): static
    {
        return $this->state(fn () => ['interval_months' => null, 'interval_km' => null]);
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }
}
