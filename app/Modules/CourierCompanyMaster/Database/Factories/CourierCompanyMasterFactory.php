<?php

namespace App\Modules\CourierCompanyMaster\Database\Factories;

use App\Modules\CourierCompanyMaster\Models\CourierCompanyMaster;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CourierCompanyMaster>
 */
class CourierCompanyMasterFactory extends Factory
{
    protected $model = CourierCompanyMaster::class;

    public function definition(): array
    {
        return [
            'name' => strtoupper($this->faker->unique()->company()),
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
