<?php

namespace App\Modules\DamageCauseMaster\Database\Factories;

use App\Modules\DamageCauseMaster\Models\DamageCauseMaster;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DamageCauseMaster>
 */
class DamageCauseMasterFactory extends Factory
{
    protected $model = DamageCauseMaster::class;

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
