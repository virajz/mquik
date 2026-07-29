<?php

namespace App\Modules\AdvisorFeedback\Database\Factories;

use App\Modules\AdvisorFeedback\Models\AdvisorFeedback;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AdvisorFeedback>
 */
class AdvisorFeedbackFactory extends Factory
{
    protected $model = AdvisorFeedback::class;

    public function definition(): array
    {
        return [
            'status' => AdvisorFeedback::STATUS_PENDING,
        ];
    }

    public function submitted(): static
    {
        return $this->state(fn () => [
            'status' => AdvisorFeedback::STATUS_SUBMITTED,
            'cooperative_rating' => 4,
            'timely_approvals_rating' => 5,
            'payment_committed_rating' => 5,
            'professional_rating' => 4,
            'prefer_again_rating' => 5,
            'submitted_at' => now(),
        ]);
    }
}
