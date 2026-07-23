<?php

namespace App\Modules\InsurancePolicyTypeMaster\Database\Factories;

use App\Modules\InsurancePolicyTypeMaster\Models\InsurancePolicyTypeMaster;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InsurancePolicyTypeMaster>
 */
class InsurancePolicyTypeMasterFactory extends Factory
{
    protected $model = InsurancePolicyTypeMaster::class;

    public function definition(): array
    {
        return [
            'name' => strtoupper($this->faker->unique()->words(2, true)),
            'code' => null,
            'default_pass_percent' => 100,
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
