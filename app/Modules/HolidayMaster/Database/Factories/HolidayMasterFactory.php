<?php

namespace App\Modules\HolidayMaster\Database\Factories;

use App\Modules\HolidayMaster\Models\HolidayMaster;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<HolidayMaster>
 */
class HolidayMasterFactory extends Factory
{
    protected $model = HolidayMaster::class;

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
