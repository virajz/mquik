<?php

namespace App\Modules\GoodsReturnNote\Database\Factories;

use App\Modules\GoodsReturnNote\Models\GoodsReturnNote;
use App\Modules\VendorMaster\Models\VendorMaster;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<GoodsReturnNote>
 */
class GoodsReturnNoteFactory extends Factory
{
    protected $model = GoodsReturnNote::class;

    public function definition(): array
    {
        return [
            'vendor_id' => VendorMaster::factory(),
            'return_type' => 'warranty',
            'claim_type' => 'outside_labour_warranty',
            'return_reason' => 'workmanship_failure',
            'warranty_type' => 'within_warranty',
            'status' => GoodsReturnNote::STATUS_REQUESTED,
            'recovery_amount' => $this->faker->numberBetween(500, 20000),
        ];
    }

    public function accepted(): static
    {
        return $this->state(fn () => ['status' => GoodsReturnNote::STATUS_FULLY_ACCEPTED]);
    }

    public function rejected(): static
    {
        return $this->state(fn () => ['status' => GoodsReturnNote::STATUS_REJECTED, 'rejection_reason' => 'warranty_expired']);
    }

    public function counterProposal(): static
    {
        return $this->state(fn () => ['status' => GoodsReturnNote::STATUS_COUNTER_PROPOSAL, 'counter_proposal' => 'shared_cost']);
    }
}
