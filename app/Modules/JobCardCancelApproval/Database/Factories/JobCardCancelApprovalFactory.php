<?php

namespace App\Modules\JobCardCancelApproval\Database\Factories;

use App\Modules\JobCard\Models\JobCard;
use App\Modules\JobCardCancelApproval\Models\JobCardCancelApproval;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<JobCardCancelApproval>
 */
class JobCardCancelApprovalFactory extends Factory
{
    protected $model = JobCardCancelApproval::class;

    public function definition(): array
    {
        return [
            'job_card_id' => JobCard::factory(),
            'cancellation_type' => 'wrong_entry',
            'approval_level' => 'l1_advisor',
            'status' => JobCardCancelApproval::STATUS_PENDING,
        ];
    }

    public function underReview(): static
    {
        return $this->state(fn () => ['status' => JobCardCancelApproval::STATUS_UNDER_REVIEW]);
    }

    public function approved(): static
    {
        return $this->state(fn () => [
            'status' => JobCardCancelApproval::STATUS_APPROVED,
            'decided_at' => now(),
            'refund_status' => 'processed',
        ]);
    }

    public function rejected(): static
    {
        return $this->state(fn () => [
            'status' => JobCardCancelApproval::STATUS_REJECTED,
            'approval_rejection_reason' => 'insufficient_reason',
            'decided_at' => now(),
        ]);
    }
}
