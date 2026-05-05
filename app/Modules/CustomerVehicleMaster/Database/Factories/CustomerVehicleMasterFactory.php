<?php

namespace App\Modules\CustomerVehicleMaster\Database\Factories;

use App\Modules\CustomerMaster\Models\CustomerMaster;
use App\Modules\CustomerVehicleMaster\Models\CustomerVehicleMaster;
use App\Modules\VehicleColorMaster\Models\VehicleColorMaster;
use App\Modules\VehicleModelMaster\Models\VehicleModelMaster;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CustomerVehicleMaster>
 */
class CustomerVehicleMasterFactory extends Factory
{
    protected $model = CustomerVehicleMaster::class;

    public function definition(): array
    {
        $stateCodes = ['GJ', 'MH', 'KA', 'TN', 'DL', 'UP', 'RJ'];
        $rto = $this->faker->numerify('##');
        $alpha = strtoupper($this->faker->lexify('??'));
        $num = $this->faker->numerify('####');
        $regNo = $this->faker->randomElement($stateCodes)." {$rto} {$alpha} {$num}";

        return [
            'customer_id' => CustomerMaster::factory(),
            'model_id' => VehicleModelMaster::factory(),
            'variant_id' => null,
            'color_id' => VehicleColorMaster::factory(),
            'registration_no' => $regNo,
            'year_of_manufacture' => $this->faker->numberBetween(2010, (int) date('Y')),
            'vin' => strtoupper($this->faker->bothify('?????????????????')),
            'engine_no' => strtoupper($this->faker->bothify('???########')),
            'odometer_km' => $this->faker->numberBetween(1000, 200000),
            'insurance_expiry' => $this->faker->dateTimeBetween('-6 months', '+1 year')->format('Y-m-d'),
            'puc_expiry' => $this->faker->dateTimeBetween('-6 months', '+1 year')->format('Y-m-d'),
            'is_active' => true,
            'notes' => null,
        ];
    }
}
