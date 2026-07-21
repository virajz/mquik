<?php

namespace App\Modules\DistanceSlabMaster\Database\Factories;

use App\Modules\DistanceSlabMaster\Models\DistanceSlabMaster;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DistanceSlabMaster>
 */
class DistanceSlabMasterFactory extends Factory
{
    protected $model = DistanceSlabMaster::class;

    public function definition(): array
    {
        return [
            'name' => strtoupper($this->faker->unique()->words(2, true)),
            'code' => null,
            'min_km' => 0,
            'max_km' => 5,
            'charge_amount' => 200,
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
