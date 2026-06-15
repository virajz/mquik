<?php

namespace App\Modules\VehicleVariantMaster\Database\Factories;

use App\Modules\FuelTypeMaster\Models\FuelTypeMaster;
use App\Modules\TransmissionTypeMaster\Models\TransmissionTypeMaster;
use App\Modules\VehicleModelMaster\Models\VehicleModelMaster;
use App\Modules\VehicleVariantMaster\Models\VehicleVariantMaster;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<VehicleVariantMaster>
 */
class VehicleVariantMasterFactory extends Factory
{
    protected $model = VehicleVariantMaster::class;

    public function definition(): array
    {
        return [
            'model_id' => VehicleModelMaster::factory(),
            'name' => $this->faker->randomElement(['LXi', 'VXi', 'ZXi', 'ZXi+']).' '.$this->faker->randomNumber(3),
            'transmission_type_id' => TransmissionTypeMaster::factory(),
            'engine_cc' => $this->faker->randomElement(['1197', '1462', '1493', '1956']).'cc',
            'fuel_type_id' => FuelTypeMaster::factory(),
            'year' => $this->faker->numberBetween(2018, (int) date('Y')),
            'is_active' => true,
            'notes' => null,
        ];
    }

    public function forModel(VehicleModelMaster $model): static
    {
        return $this->state(fn () => ['model_id' => $model->id]);
    }
}
