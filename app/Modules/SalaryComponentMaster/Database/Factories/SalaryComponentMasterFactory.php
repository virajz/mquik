<?php

namespace App\Modules\SalaryComponentMaster\Database\Factories;

use App\Modules\SalaryComponentMaster\Models\SalaryComponentMaster;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SalaryComponentMaster>
 */
class SalaryComponentMasterFactory extends Factory
{
    protected $model = SalaryComponentMaster::class;

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
