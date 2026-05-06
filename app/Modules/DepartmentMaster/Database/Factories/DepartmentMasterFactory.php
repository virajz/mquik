<?php

namespace App\Modules\DepartmentMaster\Database\Factories;

use App\Modules\DepartmentMaster\Models\DepartmentMaster;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DepartmentMaster>
 */
class DepartmentMasterFactory extends Factory
{
    protected $model = DepartmentMaster::class;

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
}
