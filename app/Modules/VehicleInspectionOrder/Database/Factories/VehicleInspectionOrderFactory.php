<?php

namespace App\Modules\VehicleInspectionOrder\Database\Factories;

use App\Modules\JobCard\Models\JobCard;
use App\Modules\VehicleInspectionOrder\Models\VehicleInspectionOrder;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<VehicleInspectionOrder>
 */
class VehicleInspectionOrderFactory extends Factory
{
    protected $model = VehicleInspectionOrder::class;

    public function definition(): array
    {
        return [
            'job_card_id' => JobCard::factory(),
            'work_priority' => 'normal',
            'status' => VehicleInspectionOrder::STATUS_ASSIGNMENT_PENDING,
        ];
    }

    public function wip(): static
    {
        return $this->state(fn () => [
            'status' => VehicleInspectionOrder::STATUS_WIP,
            'assigned_at' => now()->subHours(3),
            'started_at' => now()->subHours(2),
        ]);
    }

    public function completed(): static
    {
        return $this->state(fn () => [
            'status' => VehicleInspectionOrder::STATUS_COMPLETED,
            'completion_type' => 'fully',
            'assigned_at' => now()->subHours(4),
            'started_at' => now()->subHours(3),
            'ended_at' => now(),
        ]);
    }
}
