<?php

namespace App\Modules\Appointment\Database\Factories;

use App\Modules\Appointment\Models\Appointment;
use App\Modules\BookingChannelMaster\Models\BookingChannelMaster;
use App\Modules\CustomerMaster\Models\CustomerMaster;
use App\Modules\CustomerVehicleMaster\Models\CustomerVehicleMaster;
use App\Modules\EmployeeMaster\Models\EmployeeMaster;
use App\Modules\PickupDropOptionMaster\Models\PickupDropOptionMaster;
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
            'booking_channel_id' => self::channel('PHONE CALL', 'PHONE')->id,
            'pickup_drop_option_id' => self::pickupOption('CUSTOMER SELF DROP', 'SELF', false)->id,
            'customer_id' => $customer->id,
            'customer_vehicle_id' => $vehicle->id,
            'workshop_department_id' => WorkshopDepartmentMaster::factory(),
            'assigned_advisor_id' => EmployeeMaster::factory(),
            'status' => Appointment::STATUS_PENDING,
        ];
    }

    /**
     * Resolve a booking channel by name so tests don't have to seed the master.
     * firstOrCreate keeps the unique name/code constraints safe across rows.
     */
    private static function channel(string $name, string $code): BookingChannelMaster
    {
        return BookingChannelMaster::firstOrCreate(
            ['name' => $name],
            ['code' => $code, 'is_active' => true],
        );
    }

    private static function pickupOption(string $name, string $code, bool $involvesPickup): PickupDropOptionMaster
    {
        return PickupDropOptionMaster::firstOrCreate(
            ['name' => $name],
            ['code' => $code, 'involves_pickup' => $involvesPickup, 'involves_drop' => false, 'is_active' => true],
        );
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

    /** An appointment the workshop must collect the vehicle for. */
    public function pickup(): static
    {
        return $this->state(fn () => [
            'pickup_drop_option_id' => self::pickupOption('WORKSHOP PICKUP ONLY', 'WPU', true)->id,
            'pickup_address' => strtoupper($this->faker->address()),
            'pickup_contact_phone' => $this->faker->numerify('##########'),
        ]);
    }
}
