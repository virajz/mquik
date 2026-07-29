<?php

namespace App\Modules\GatePassApproval\Database\Factories;

use App\Modules\GatePassApproval\Models\GatePassApproval;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<GatePassApproval>
 */
class GatePassApprovalFactory extends Factory
{
    protected $model = GatePassApproval::class;

    public function definition(): array
    {
        $invoice = $this->faker->numberBetween(10000, 60000);
        $receipt = $this->faker->numberBetween(0, $invoice);
        $outstanding = $invoice - $receipt;

        return [
            'priority' => 'normal',
            'credit_type' => 'partial_pending',
            'credit_reason' => 'regular_customer',
            'risk_type' => 'low',
            'status' => GatePassApproval::STATUS_REQUESTED,
            'invoice_amount' => $invoice,
            'receipt_amount' => $receipt,
            'outstanding_amount' => $outstanding,
            'credit_exposure' => $outstanding,
            'approval_authority' => GatePassApproval::authorityForAmount($outstanding),
            'requested_at' => now(),
        ];
    }

    public function fullApproved(): static
    {
        return $this->state(fn () => ['status' => GatePassApproval::STATUS_FULL_APPROVED, 'approved_at' => now()]);
    }

    public function partialApproved(): static
    {
        return $this->state(fn () => ['status' => GatePassApproval::STATUS_PARTIAL_APPROVED, 'approved_at' => now()]);
    }

    public function rejected(): static
    {
        return $this->state(fn () => ['status' => GatePassApproval::STATUS_REJECTED, 'rejected_at' => now()]);
    }
}
