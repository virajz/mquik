<?php

namespace App\Modules\CustomerFeedback\Database\Factories;

use App\Modules\CustomerFeedback\Models\CustomerFeedback;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CustomerFeedback>
 */
class CustomerFeedbackFactory extends Factory
{
    protected $model = CustomerFeedback::class;

    public function definition(): array
    {
        return [
            'follow_up_schedule' => '4_days',
            'follow_up_category' => 'feedback_collection',
            'follow_up_mode' => 'whatsapp',
            'follow_up_attempt' => 'first',
            'feedback_source' => 'whatsapp',
            'status' => CustomerFeedback::STATUS_SENT,
            'requested_at' => now(),
        ];
    }

    public function satisfied(): static
    {
        return $this->state(fn () => [
            'status' => CustomerFeedback::STATUS_SATISFIED,
            'service_rating' => 5,
            'service_experience_rating' => 5,
            'staff_experience_rating' => 4,
            'would_recommend' => true,
            'submitted_at' => now(),
        ]);
    }

    public function dissatisfied(): static
    {
        return $this->state(fn () => [
            'status' => CustomerFeedback::STATUS_DISSATISFIED,
            'service_rating' => 2,
            'would_recommend' => false,
            'submitted_at' => now(),
        ]);
    }
}
