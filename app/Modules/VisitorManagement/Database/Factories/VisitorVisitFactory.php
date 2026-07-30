<?php

namespace App\Modules\VisitorManagement\Database\Factories;

use App\Modules\VisitorManagement\Models\VisitorVisit;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<VisitorVisit>
 */
class VisitorVisitFactory extends Factory
{
    protected $model = VisitorVisit::class;

    public function definition(): array
    {
        return [
            'customer_type' => 'gents_customer',
            'visit_purpose' => 'periodic_maintenance',
            'arrival_mode' => 'walk_in',
            'advisor_assignment_method' => 'auto',
            'advisor_availability' => 'available',
            'waiting_time_category' => 'lt_10',
            'status' => VisitorVisit::STATUS_ADVISOR_ASSIGNED,
            'arrival_at' => now(),
            'advisor_assigned_at' => now(),
        ];
    }

    public function served(): static
    {
        return $this->state(fn () => [
            'status' => VisitorVisit::STATUS_JOB_CARD_CREATED,
            'arrival_at' => now()->subMinutes(30),
            'advisor_assigned_at' => now()->subMinutes(28),
            'consultation_started_at' => now()->subMinutes(15),
            'consultation_ended_at' => now()->subMinutes(5),
            'job_card_created_at' => now(),
            'exit_at' => now(),
        ]);
    }

    public function noShow(): static
    {
        return $this->state(fn () => [
            'status' => VisitorVisit::STATUS_CANCELLED,
            'no_show_reason' => 'customer_left',
            'exit_at' => now(),
        ]);
    }

    public function today(): static
    {
        return $this->state(fn () => ['arrival_at' => now(), 'created_at' => now()]);
    }
}
