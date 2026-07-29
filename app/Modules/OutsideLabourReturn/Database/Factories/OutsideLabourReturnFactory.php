<?php

namespace App\Modules\OutsideLabourReturn\Database\Factories;

use App\Modules\OutsideLabourReturn\Models\OutsideLabourReturn;
use App\Modules\VendorMaster\Models\VendorMaster;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OutsideLabourReturn>
 */
class OutsideLabourReturnFactory extends Factory
{
    protected $model = OutsideLabourReturn::class;

    public function definition(): array
    {
        return [
            'vendor_id' => VendorMaster::factory(),
            'return_type' => 'warranty',
            'claim_type' => 'outside_labour_warranty',
            'return_reason' => 'workmanship_failure',
            'warranty_type' => 'within_warranty',
            'status' => OutsideLabourReturn::STATUS_REQUESTED,
            'recovery_amount' => $this->faker->numberBetween(500, 20000),
        ];
    }

    public function accepted(): static
    {
        return $this->state(fn () => ['status' => OutsideLabourReturn::STATUS_FULLY_ACCEPTED]);
    }

    public function rejected(): static
    {
        return $this->state(fn () => ['status' => OutsideLabourReturn::STATUS_REJECTED, 'rejection_reason' => 'warranty_expired']);
    }

    public function counterProposal(): static
    {
        return $this->state(fn () => ['status' => OutsideLabourReturn::STATUS_COUNTER_PROPOSAL, 'counter_proposal' => 'shared_cost']);
    }
}
