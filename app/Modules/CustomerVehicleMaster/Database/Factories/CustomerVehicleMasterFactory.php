<?php

namespace App\Modules\CustomerVehicleMaster\Database\Factories;

use App\Modules\CustomerMaster\Models\CustomerMaster;
use App\Modules\CustomerVehicleMaster\Models\CustomerVehicleMaster;
use App\Modules\RegistrationTypeMaster\Models\RegistrationTypeMaster;
use App\Modules\VehicleColorMaster\Models\VehicleColorMaster;
use App\Modules\VehicleVariantMaster\Models\VehicleVariantMaster;
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
        // Stored without spaces; form layer strips whitespace before save.
        $regNo = $this->faker->randomElement($stateCodes).$rto.$alpha.$num;

        // Create a variant with its model auto-built; bind both to keep the
        // (model_id, variant_id) pair consistent so factory-built rows
        // round-trip cleanly through the form.
        $variant = VehicleVariantMaster::factory()->create();

        return [
            'customer_id' => CustomerMaster::factory(),
            'model_id' => $variant->model_id,
            'variant_id' => $variant->id,
            'color_id' => VehicleColorMaster::factory(),
            'registration_no' => $regNo,
            'registration_type_id' => RegistrationTypeMaster::factory(),
            'year_of_manufacture' => $this->faker->numberBetween(2010, (int) date('Y')),
            'vin' => strtoupper($this->faker->bothify('?????????????????')),
            'engine_no' => strtoupper($this->faker->bothify('???########')),
            'odometer_km' => $this->faker->numberBetween(1000, 200000),
            'is_active' => true,
            'notes' => null,
        ];
    }
}
