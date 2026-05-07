<?php

namespace App\Modules\VehicleModelMaster\Database\Factories;

use App\Modules\VehicleBrandMaster\Models\VehicleBrandMaster;
use App\Modules\VehicleModelMaster\Models\VehicleModelMaster;
use App\Modules\VehicleSegmentMaster\Models\VehicleSegmentMaster;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<VehicleModelMaster>
 */
class VehicleModelMasterFactory extends Factory
{
    protected $model = VehicleModelMaster::class;

    public function definition(): array
    {
        return [
            'brand_id' => VehicleBrandMaster::factory(),
            'name' => strtoupper($this->faker->unique()->word()).' '.$this->faker->randomNumber(3),
            'vehicle_segment_id' => VehicleSegmentMaster::factory(),
            'fuel_type' => $this->faker->randomElement(['petrol', 'diesel', 'cng', 'electric']),
            'is_active' => true,
            'notes' => null,
        ];
    }

    public function forBrand(VehicleBrandMaster $brand): static
    {
        return $this->state(fn () => ['brand_id' => $brand->id]);
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }
}
