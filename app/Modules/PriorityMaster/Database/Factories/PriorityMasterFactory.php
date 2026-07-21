<?php

namespace App\Modules\PriorityMaster\Database\Factories;

use App\Modules\PriorityMaster\Models\PriorityMaster;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PriorityMaster>
 */
class PriorityMasterFactory extends Factory
{
    protected $model = PriorityMaster::class;

    public function definition(): array
    {
        return [
            'name' => strtoupper($this->faker->unique()->words(2, true)),
            'code' => null,
            'sort_order' => 0,
            'applies_to' => PriorityMaster::APPLIES_BOTH,
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
