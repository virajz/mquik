<?php

namespace App\Modules\VehicleMovement\Database\Factories;

use App\Modules\VehicleMovement\Models\VehicleMovement;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<VehicleMovement>
 */
class VehicleMovementFactory extends Factory
{
    protected $model = VehicleMovement::class;

    public function definition(): array
    {
        return [
            'movement_type' => VehicleMovement::TYPE_INWARD,
            'parking_slot' => 'slot_1',
            'gate' => 'gate_1',
            'job_status' => VehicleMovement::JOB_PENDING,
            'number_plate' => strtoupper($this->faker->bothify('GJ##??####')),
            'entry_at' => now(),
        ];
    }

    public function outward(): static
    {
        return $this->state(fn () => ['movement_type' => VehicleMovement::TYPE_OUTWARD, 'outward_type' => 'final_delivery']);
    }

    public function trialRun(): static
    {
        return $this->state(fn () => ['movement_type' => VehicleMovement::TYPE_OUTWARD, 'outward_type' => 'trial_run']);
    }

    public function completed(): static
    {
        return $this->state(fn () => ['job_status' => VehicleMovement::JOB_COMPLETED, 'exit_at' => now()->addHours(2)]);
    }
}
