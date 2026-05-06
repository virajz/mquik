<?php

namespace App\Modules\VehicleColorMaster\Database\Factories;

use App\Modules\VehicleColorMaster\Models\VehicleColorMaster;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<VehicleColorMaster>
 */
class VehicleColorMasterFactory extends Factory
{
    protected $model = VehicleColorMaster::class;

    public function definition(): array
    {
        return [
            'name' => strtoupper($this->faker->unique()->safeColorName()).' '.$this->faker->randomNumber(3),
            'hex_code' => '#'.strtoupper(sprintf('%06X', random_int(0, 0xFFFFFF))),
            'is_active' => true,
            'notes' => null,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }
}
