<?php

namespace App\Modules\InventoryGroupMaster\Database\Factories;

use App\Modules\InventoryGroupMaster\Models\InventoryGroupMaster;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InventoryGroupMaster>
 */
class InventoryGroupMasterFactory extends Factory
{
    protected $model = InventoryGroupMaster::class;

    public function definition(): array
    {
        return [
            'name' => strtoupper($this->faker->unique()->words(2, true)),
            'code' => null,
            'parent_id' => null,
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

    public function childOf(InventoryGroupMaster $parent): static
    {
        return $this->state(fn () => ['parent_id' => $parent->id]);
    }
}
