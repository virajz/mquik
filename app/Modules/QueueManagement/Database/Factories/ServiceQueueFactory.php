<?php

namespace App\Modules\QueueManagement\Database\Factories;

use App\Modules\QueueManagement\Models\ServiceQueue;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ServiceQueue>
 */
class ServiceQueueFactory extends Factory
{
    protected $model = ServiceQueue::class;

    public function definition(): array
    {
        return [
            'queue_type' => 'car_wash',
            'ordering_mode' => 'fifo',
            'screen_view' => 'upcoming',
            'status' => ServiceQueue::STATUS_WAITING,
            'kept_at' => now()->subMinutes(30),
        ];
    }

    public function inProgress(): static
    {
        return $this->state(fn () => [
            'status' => ServiceQueue::STATUS_IN_PROGRESS,
            'screen_view' => 'arrived',
            'work_started_at' => now()->subMinutes(15),
        ]);
    }

    public function completed(): static
    {
        return $this->state(fn () => [
            'status' => ServiceQueue::STATUS_COMPLETED,
            'screen_view' => 'ready',
            'kept_at' => now()->subMinutes(60),
            'work_started_at' => now()->subMinutes(45),
            'work_ended_at' => now()->subMinutes(15),
            'promised_delivery_at' => now(),
            'expected_completion_at' => now(),
        ]);
    }

    public function highPriority(): static
    {
        return $this->state(fn () => [
            'is_high_priority' => true,
            'ordering_mode' => 'priority',
            'high_priority_reason' => 'customer_waiting',
        ]);
    }
}
