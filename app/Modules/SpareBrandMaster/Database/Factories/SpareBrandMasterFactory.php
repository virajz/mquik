<?php

namespace App\Modules\SpareBrandMaster\Database\Factories;

use App\Modules\SpareBrandMaster\Models\SpareBrandMaster;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SpareBrandMaster>
 */
class SpareBrandMasterFactory extends Factory
{
    protected $model = SpareBrandMaster::class;

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
