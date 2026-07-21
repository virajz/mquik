<?php

namespace App\Modules\PickupDrop\Database\Factories;

use App\Modules\CustomerMaster\Models\CustomerMaster;
use App\Modules\CustomerVehicleMaster\Models\CustomerVehicleMaster;
use App\Modules\EmployeeMaster\Models\EmployeeMaster;
use App\Modules\PickupDrop\Models\PickupDrop;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PickupDrop>
 */
class PickupDropFactory extends Factory
{
    protected $model = PickupDrop::class;

    public function definition(): array
    {
        $customer = CustomerMaster::factory()->create();
        $vehicle = CustomerVehicleMaster::factory()->create(['customer_id' => $customer->id]);

        return [
            'direction' => PickupDrop::DIRECTION_PICKUP,
            'customer_id' => $customer->id,
            'customer_vehicle_id' => $vehicle->id,
            'scheduled_at' => $this->faker->dateTimeBetween('-3 days', '+1 week'),
            'pickup_address' => strtoupper($this->faker->address()),
            'contact_phone' => $this->faker->numerify('##########'),
            'driver_employee_id' => EmployeeMaster::factory(),
            'status' => PickupDrop::STATUS_PENDING,
            'otp_mode' => PickupDrop::OTP_OPTIONAL,
        ];
    }

    public function picked(): static
    {
        return $this->state(fn () => ['status' => PickupDrop::STATUS_VEHICLE_COLLECTED]);
    }

    public function delivered(): static
    {
        return $this->state(fn () => ['status' => PickupDrop::STATUS_VEHICLE_DELIVERED]);
    }

    public function drop(): static
    {
        return $this->state(fn () => ['direction' => PickupDrop::DIRECTION_DROP]);
    }
}
