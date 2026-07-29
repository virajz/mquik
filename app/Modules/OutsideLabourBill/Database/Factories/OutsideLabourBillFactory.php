<?php

namespace App\Modules\OutsideLabourBill\Database\Factories;

use App\Modules\OutsideLabourBill\Models\OutsideLabourBill;
use App\Modules\VendorMaster\Models\VendorMaster;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OutsideLabourBill>
 */
class OutsideLabourBillFactory extends Factory
{
    protected $model = OutsideLabourBill::class;

    public function definition(): array
    {
        return [
            'vendor_id' => VendorMaster::factory(),
            'bill_document_type' => 'tax_invoice',
            'bill_amount' => $this->faker->numberBetween(1000, 40000),
            'work_completion_type' => 'fully_completed',
            'status' => OutsideLabourBill::STATUS_REQUESTED,
        ];
    }

    public function onHold(): static
    {
        return $this->state(fn () => ['status' => OutsideLabourBill::STATUS_ON_HOLD, 'hold_reason' => 'rate_difference']);
    }

    public function rejected(): static
    {
        return $this->state(fn () => ['status' => OutsideLabourBill::STATUS_REJECTED, 'rejection_reason' => 'rate_mismatch']);
    }

    public function verified(): static
    {
        return $this->state(fn () => ['status' => OutsideLabourBill::STATUS_FULLY_VERIFIED]);
    }
}
