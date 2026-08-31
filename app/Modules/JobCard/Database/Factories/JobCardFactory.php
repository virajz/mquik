<?php

namespace App\Modules\JobCard\Database\Factories;

use App\Modules\CustomerMaster\Models\CustomerMaster;
use App\Modules\CustomerVehicleMaster\Models\CustomerVehicleMaster;
use App\Modules\EmployeeMaster\Models\EmployeeMaster;
use App\Modules\GateInOut\Models\GateInOut;
use App\Modules\JobCard\Models\JobCard;
use App\Modules\ServiceTypeMaster\Models\ServiceTypeMaster;
use App\Modules\WorkshopDepartmentMaster\Models\WorkshopDepartmentMaster;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<JobCard>
 */
class JobCardFactory extends Factory
{
    protected $model = JobCard::class;

    public function definition(): array
    {
        $customer = CustomerMaster::factory()->create();
        $vehicle = CustomerVehicleMaster::factory()->create(['customer_id' => $customer->id]);
        $opened = $this->faker->dateTimeBetween('-1 week', 'now');

        return [
            'customer_id' => $customer->id,
            'customer_vehicle_id' => $vehicle->id,
            // A card always comes off a gate entry now, so the factory gives it
            // one — the same vehicle, or the card would describe two cars.
            'gate_event_id' => GateInOut::factory()->create([
                'customer_id' => $customer->id,
                'customer_vehicle_id' => $vehicle->id,
            ]),
            'service_type_id' => ServiceTypeMaster::factory(),
            'assigned_technician_id' => EmployeeMaster::factory(),
            'terms_accepted_by' => 'customer',
            'workshop_department_id' => WorkshopDepartmentMaster::factory(),
            'assigned_advisor_id' => EmployeeMaster::factory(),
            'opened_at' => $opened,
            'promised_at' => (clone $opened)->modify('+1 day'),
            'km_at_service' => $this->faker->numberBetween(5000, 150000),
            'fuel_level' => $this->faker->randomElement(array_keys(JobCard::fuelLevels())),
            'terms_accepted' => true,
            'terms_accepted_at' => $opened,
            'status' => JobCard::STATUS_OPEN,
        ];
    }

    public function inProgress(): static
    {
        return $this->state(fn () => ['status' => JobCard::STATUS_IN_PROGRESS]);
    }

    public function completed(): static
    {
        return $this->state(fn () => [
            'status' => JobCard::STATUS_COMPLETED,
            'closed_at' => now(),
        ]);
    }
}
