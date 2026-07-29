<?php

namespace App\Modules\AdvanceReceiptRequest\Database\Factories;

use App\Modules\AdvanceReceiptRequest\Models\AdvanceReceiptRequest;
use App\Modules\JobCard\Models\JobCard;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AdvanceReceiptRequest>
 */
class AdvanceReceiptRequestFactory extends Factory
{
    protected $model = AdvanceReceiptRequest::class;

    public function definition(): array
    {
        return [
            'job_card_id' => JobCard::factory(),
            'advance_purpose' => 'regular_parts',
            'amount_type' => 'custom',
            'amount' => $this->faker->numberBetween(500, 10000),
            'payment_status' => AdvanceReceiptRequest::STATUS_REQUESTED,
        ];
    }

    public function partiallyPaid(): static
    {
        return $this->state(fn () => ['payment_status' => AdvanceReceiptRequest::STATUS_PARTIALLY_PAID]);
    }

    public function fullyPaid(): static
    {
        return $this->state(fn () => ['payment_status' => AdvanceReceiptRequest::STATUS_FULLY_PAID]);
    }

    public function rejected(): static
    {
        return $this->state(fn () => [
            'payment_status' => AdvanceReceiptRequest::STATUS_REJECTED,
            'rejection_reason' => 'budget_issue',
        ]);
    }
}
