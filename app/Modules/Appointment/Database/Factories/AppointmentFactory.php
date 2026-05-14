<?php

namespace App\Modules\Appointment\Database\Factories;

use App\Modules\Appointment\Models\Appointment;
use App\Modules\CustomerMaster\Models\CustomerMaster;
use App\Modules\CustomerVehicleMaster\Models\CustomerVehicleMaster;
use App\Modules\EmployeeMaster\Models\EmployeeMaster;
use App\Modules\WorkshopDepartmentMaster\Models\WorkshopDepartmentMaster;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Appointment>
 */
class AppointmentFactory extends Factory
{
    protected $model = Appointment::class;

    public function definition(): array
    {
        $customer = CustomerMaster::factory()->create();
        $vehicle = CustomerVehicleMaster::factory()->create(['customer_id' => $customer->id]);

        return [
            'appointment_at' => $this->faker->dateTimeBetween('-2 weeks', '+2 weeks'),
            'channel' => $this->faker->randomElement(array_keys(Appointment::channels())),
            'customer_id' => $customer->id,
            'customer_vehicle_id' => $vehicle->id,
            'workshop_department_id' => WorkshopDepartmentMaster::factory(),
            'assigned_advisor_id' => EmployeeMaster::factory(),
            'requires_pickup' => false,
            'status' => Appointment::STATUS_PENDING,
        ];
    }

    public function confirmed(): static
    {
        return $this->state(fn () => ['status' => Appointment::STATUS_CONFIRMED]);
    }

    public function completed(): static
    {
        return $this->state(fn () => ['status' => Appointment::STATUS_COMPLETED]);
    }

    public function cancelled(): static
    {
        return $this->state(fn () => ['status' => Appointment::STATUS_CANCELLED]);
    }

    public function pickup(): static
    {
        return $this->state(fn () => [
            'requires_pickup' => true,
            'pickup_address' => strtoupper($this->faker->address()),
            'pickup_contact_phone' => $this->faker->numerify('##########'),
        ]);
    }
}
