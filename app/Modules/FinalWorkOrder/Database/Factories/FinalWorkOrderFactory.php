<?php

namespace App\Modules\FinalWorkOrder\Database\Factories;

use App\Modules\FinalWorkOrder\Models\FinalWorkOrder;
use App\Modules\JobCard\Models\JobCard;
use App\Modules\PriorityMaster\Models\PriorityMaster;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FinalWorkOrder>
 */
class FinalWorkOrderFactory extends Factory
{
    protected $model = FinalWorkOrder::class;

    public function definition(): array
    {
        return [
            'job_card_id' => JobCard::factory(),
            'priority_id' => PriorityMaster::firstOrCreate(
                ['name' => 'NORMAL'],
                ['code' => 'NRM', 'sort_order' => 10, 'applies_to' => 'both', 'is_active' => true],
            )->id,
            'status' => FinalWorkOrder::STATUS_ASSIGNMENT_PENDING,
        ];
    }

    public function wip(): static
    {
        return $this->state(fn () => [
            'status' => FinalWorkOrder::STATUS_WIP,
            'assigned_at' => now()->subHours(3),
            'started_at' => now()->subHours(2),
        ]);
    }

    public function completed(): static
    {
        return $this->state(fn () => [
            'status' => FinalWorkOrder::STATUS_COMPLETED,
            'completion_type' => 'fully',
            'assigned_at' => now()->subHours(4),
            'started_at' => now()->subHours(3),
            'ended_at' => now(),
        ]);
    }
}
