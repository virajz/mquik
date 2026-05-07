<?php

namespace App\Modules\BankMaster\Database\Factories;

use App\Modules\BankMaster\Models\BankMaster;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BankMaster>
 */
class BankMasterFactory extends Factory
{
    protected $model = BankMaster::class;

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
