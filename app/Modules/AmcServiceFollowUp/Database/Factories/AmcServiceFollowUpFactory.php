<?php

namespace App\Modules\AmcServiceFollowUp\Database\Factories;

use App\Modules\AmcServiceFollowUp\Models\AmcServiceFollowUp;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AmcServiceFollowUp>
 */
class AmcServiceFollowUpFactory extends Factory
{
    protected $model = AmcServiceFollowUp::class;

    public function definition(): array
    {
        return [
            'follow_up_type' => 'service_due',
            'status' => AmcServiceFollowUp::STATUS_PENDING,
            'service_interval_method' => 'date',
            'service_interval' => '6_month',
            'reminder_frequency' => 'before_7_days',
            'customer_retention' => 'active',
            'due_generated_at' => now(),
        ];
    }

    public function converted(): static
    {
        return $this->state(fn () => ['status' => AmcServiceFollowUp::STATUS_CONVERTED, 'job_card_open_at' => now()]);
    }

    public function appointmentBooked(): static
    {
        return $this->state(fn () => ['status' => AmcServiceFollowUp::STATUS_APPOINTMENT_BOOKED, 'appointment_at' => now()]);
    }

    public function lost(): static
    {
        return $this->state(fn () => ['status' => AmcServiceFollowUp::STATUS_LOST, 'customer_retention' => 'lost', 'lost_reason' => 'price_concern']);
    }

    public function recovered(): static
    {
        return $this->state(fn () => ['customer_retention' => 'recovered']);
    }
}
