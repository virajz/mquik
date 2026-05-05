<?php

namespace App\Modules\VehicleBrandMaster\Database\Factories;

use App\Modules\VehicleBrandMaster\Models\VehicleBrandMaster;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<VehicleBrandMaster>
 */
class VehicleBrandMasterFactory extends Factory
{
    protected $model = VehicleBrandMaster::class;

    public function definition(): array
    {
        return [
            'name' => strtoupper($this->faker->unique()->company()),
            'code' => null,
            'country' => null,
            'is_active' => true,
            'notes' => null,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }
}
