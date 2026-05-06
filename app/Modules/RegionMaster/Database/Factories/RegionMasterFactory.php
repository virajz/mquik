<?php

namespace App\Modules\RegionMaster\Database\Factories;

use App\Modules\RegionMaster\Models\RegionMaster;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RegionMaster>
 */
class RegionMasterFactory extends Factory
{
    protected $model = RegionMaster::class;

    /**
     * Default to creating a state.
     */
    public function definition(): array
    {
        return [
            'kind' => 'state',
            'name' => strtoupper($this->faker->unique()->state()),
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

    /**
     * Top-level state row.
     *
     * (Named asState() — not state() — to avoid shadowing Factory::state(callable).)
     */
    public function asState(): static
    {
        return $this->state(fn () => [
            'kind' => 'state',
            'parent_id' => null,
        ]);
    }

    public function city(?int $stateId = null): static
    {
        return $this->state(function () use ($stateId) {
            $parentId = $stateId ?? RegionMaster::factory()->asState()->create()->id;

            return [
                'kind' => 'city',
                'parent_id' => $parentId,
                'name' => strtoupper($this->faker->unique()->city()),
            ];
        });
    }

    public function area(?int $cityId = null): static
    {
        return $this->state(function () use ($cityId) {
            $parentId = $cityId ?? RegionMaster::factory()->city()->create()->id;

            return [
                'kind' => 'area',
                'parent_id' => $parentId,
                'name' => strtoupper($this->faker->unique()->streetName()),
            ];
        });
    }

    public function pincode(?int $areaId = null): static
    {
        return $this->state(function () use ($areaId) {
            $parentId = $areaId ?? RegionMaster::factory()->area()->create()->id;

            return [
                'kind' => 'pincode',
                'parent_id' => $parentId,
                'name' => (string) $this->faker->unique()->numberBetween(100000, 999999),
            ];
        });
    }
}
