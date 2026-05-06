<?php

namespace App\Modules\DesignationMaster\Database\Factories;

use App\Modules\DesignationMaster\Models\DesignationMaster;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DesignationMaster>
 */
class DesignationMasterFactory extends Factory
{
    protected $model = DesignationMaster::class;

    public function definition(): array
    {
        return [
            'name' => strtoupper($this->faker->unique()->jobTitle()),
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
