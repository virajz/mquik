<?php

namespace App\Modules\PolicyRenewalFollowUp\Database\Factories;

use App\Modules\PolicyRenewalFollowUp\Models\PolicyRenewalFollowUp;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PolicyRenewalFollowUp>
 */
class PolicyRenewalFollowUpFactory extends Factory
{
    protected $model = PolicyRenewalFollowUp::class;

    public function definition(): array
    {
        return [
            'policy_number' => 'POL-'.$this->faker->numberBetween(100000, 999999),
            'priority' => 'normal',
            'status' => PolicyRenewalFollowUp::STATUS_PENDING,
            'reminder_frequency' => 'before_7_days',
            'customer_retention' => 'active',
            'renewal_premium' => $this->faker->numberBetween(5000, 40000),
        ];
    }

    public function renewed(): static
    {
        return $this->state(fn () => ['status' => PolicyRenewalFollowUp::STATUS_RENEWED, 'policy_issued_at' => now()]);
    }

    public function overdue(): static
    {
        return $this->state(fn () => ['status' => PolicyRenewalFollowUp::STATUS_OVERDUE, 'policy_end_date' => now()->subDays(5)]);
    }

    public function lost(): static
    {
        return $this->state(fn () => ['status' => PolicyRenewalFollowUp::STATUS_LOST, 'customer_retention' => 'lost', 'lost_reason' => 'lower_premium_elsewhere']);
    }
}
