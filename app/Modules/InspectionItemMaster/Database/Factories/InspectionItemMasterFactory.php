<?php

namespace App\Modules\InspectionItemMaster\Database\Factories;

use App\Modules\InspectionItemGroupMaster\Models\InspectionItemGroupMaster;
use App\Modules\InspectionItemMaster\Models\InspectionItemMaster;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InspectionItemMaster>
 */
class InspectionItemMasterFactory extends Factory
{
    protected $model = InspectionItemMaster::class;

    public function definition(): array
    {
        return [
            'name' => strtoupper($this->faker->unique()->words(3, true)),
            'code' => null,
            'inspection_item_group_id' => InspectionItemGroupMaster::factory(),
            'check_type' => 'visual',
            'measurement_unit' => null,
            'is_active' => true,
            'notes' => null,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }

    public function measurement(?string $unit = null): static
    {
        return $this->state(fn () => [
            'check_type' => 'measurement',
            'measurement_unit' => $unit,
        ]);
    }

    public function yesNo(): static
    {
        return $this->state(fn () => ['check_type' => 'yes_no']);
    }

    public function rating(): static
    {
        return $this->state(fn () => ['check_type' => 'rating']);
    }

    public function withoutGroup(): static
    {
        return $this->state(fn () => ['inspection_item_group_id' => null]);
    }
}
