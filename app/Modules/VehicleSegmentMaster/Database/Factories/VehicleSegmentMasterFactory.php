<?php

namespace App\Modules\VehicleSegmentMaster\Database\Factories;

use App\Modules\VehicleSegmentMaster\Models\VehicleSegmentMaster;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<VehicleSegmentMaster>
 */
class VehicleSegmentMasterFactory extends Factory
{
    protected $model = VehicleSegmentMaster::class;

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
