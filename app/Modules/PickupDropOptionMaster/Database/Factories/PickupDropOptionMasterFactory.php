<?php

namespace App\Modules\PickupDropOptionMaster\Database\Factories;

use App\Modules\PickupDropOptionMaster\Models\PickupDropOptionMaster;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PickupDropOptionMaster>
 */
class PickupDropOptionMasterFactory extends Factory
{
    protected $model = PickupDropOptionMaster::class;

    public function definition(): array
    {
        return [
            'name' => strtoupper($this->faker->unique()->words(2, true)),
            'code' => null,
            'involves_pickup' => false,
            'involves_drop' => false,
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
