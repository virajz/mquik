<?php

namespace App\Modules\InternalWorkOrder\Database\Factories;

use App\Modules\EmployeeMaster\Models\EmployeeMaster;
use App\Modules\InternalWorkOrder\Models\InternalWorkOrder;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InternalWorkOrder>
 */
class InternalWorkOrderFactory extends Factory
{
    protected $model = InternalWorkOrder::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'iwo_type' => 'raise_complaint',
            'iwo_category' => 'hardware',
            'priority' => 'normal',
            'department' => 'it',
            'status' => InternalWorkOrder::STATUS_REQUESTED,
            'title' => strtoupper($this->faker->words(3, true)),
            'description' => strtoupper($this->faker->sentence()),
            'complaint_at' => now(),
        ];
    }

    public function assigned(): static
    {
        return $this->state(fn () => [
            'status' => InternalWorkOrder::STATUS_IN_PROGRESS,
            'assigned_to_id' => EmployeeMaster::factory(),
            'assigned_at' => now(),
            'work_started_at' => now(),
        ]);
    }

    public function overdue(): static
    {
        return $this->state(fn () => [
            'status' => InternalWorkOrder::STATUS_IN_PROGRESS,
            'due_at' => now()->subDays(2),
        ]);
    }

    public function resolvedToday(): static
    {
        return $this->state(fn () => [
            'status' => InternalWorkOrder::STATUS_RESOLVED,
            'corrective_action' => 'repair',
            'complaint_at' => now()->subHours(4),
            'resolved_at' => now(),
        ]);
    }
}
