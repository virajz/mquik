<?php

namespace App\Modules\ProformaApproval\Database\Factories;

use App\Modules\ProformaApproval\Models\ProformaApproval;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProformaApproval>
 */
class ProformaApprovalFactory extends Factory
{
    protected $model = ProformaApproval::class;

    public function definition(): array
    {
        return [
            'approval_stage' => 'stage_1',
            'approval_authority' => 'billing_executive',
            'priority' => 'normal',
            'status' => ProformaApproval::STATUS_UNDER_PREPARATION,
            'amount' => $this->faker->numberBetween(5000, 80000),
            'requested_at' => now(),
        ];
    }

    public function storePending(): static
    {
        return $this->state(fn () => ['status' => ProformaApproval::STATUS_STORE_PENDING]);
    }

    public function advisorPending(): static
    {
        return $this->state(fn () => ['status' => ProformaApproval::STATUS_ADVISOR_PENDING]);
    }

    public function adminPending(): static
    {
        return $this->state(fn () => ['status' => ProformaApproval::STATUS_ADMIN_PENDING]);
    }

    public function converted(): static
    {
        return $this->state(fn () => ['status' => ProformaApproval::STATUS_CONVERTED, 'converted_at' => now()]);
    }
}
