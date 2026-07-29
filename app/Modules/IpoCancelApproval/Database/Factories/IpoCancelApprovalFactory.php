<?php

namespace App\Modules\IpoCancelApproval\Database\Factories;

use App\Modules\IpoCancelApproval\Models\IpoCancelApproval;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<IpoCancelApproval>
 */
class IpoCancelApprovalFactory extends Factory
{
    protected $model = IpoCancelApproval::class;

    public function definition(): array
    {
        return [
            'cancellation_reason' => 'excess_qty',
            'cancellation_category' => 'operational_error',
            'status' => IpoCancelApproval::STATUS_UNDER_REVIEW,
        ];
    }

    public function approved(): static
    {
        return $this->state(fn () => ['status' => IpoCancelApproval::STATUS_APPROVED, 'decided_at' => now()]);
    }

    public function rejected(): static
    {
        return $this->state(fn () => ['status' => IpoCancelApproval::STATUS_REJECTED, 'rejection_reason' => 'invalid_reason', 'decided_at' => now()]);
    }
}
