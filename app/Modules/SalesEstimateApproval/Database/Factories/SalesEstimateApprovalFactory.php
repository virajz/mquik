<?php

namespace App\Modules\SalesEstimateApproval\Database\Factories;

use App\Modules\JobCard\Models\JobCard;
use App\Modules\SalesEstimateApproval\Models\SalesEstimateApproval;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SalesEstimateApproval>
 */
class SalesEstimateApprovalFactory extends Factory
{
    protected $model = SalesEstimateApproval::class;

    public function definition(): array
    {
        return [
            'job_card_id' => JobCard::factory(),
            'approval_type' => 'regular',
            'approval_authorisation' => 'customer',
            'status' => SalesEstimateApproval::STATUS_SENT,
        ];
    }

    public function pendingInsurance(): static
    {
        return $this->state(fn () => ['approval_authorisation' => 'insurance']);
    }

    public function fullyApproved(): static
    {
        return $this->state(fn () => [
            'status' => SalesEstimateApproval::STATUS_FULLY_APPROVED,
            'customer_approved_at' => now(),
            'approved_at' => now(),
        ]);
    }

    public function rejected(): static
    {
        return $this->state(fn () => [
            'status' => SalesEstimateApproval::STATUS_REJECTED,
            'rejection_reason' => 'high_cost',
        ]);
    }
}
