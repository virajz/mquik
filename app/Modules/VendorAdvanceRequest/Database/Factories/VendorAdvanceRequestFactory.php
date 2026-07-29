<?php

namespace App\Modules\VendorAdvanceRequest\Database\Factories;

use App\Modules\VendorAdvanceRequest\Models\VendorAdvanceRequest;
use App\Modules\VendorMaster\Models\VendorMaster;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<VendorAdvanceRequest>
 */
class VendorAdvanceRequestFactory extends Factory
{
    protected $model = VendorAdvanceRequest::class;

    public function definition(): array
    {
        return [
            'vendor_id' => VendorMaster::factory(),
            'advance_reason' => 'vendor_requires_advance',
            'amount' => $this->faker->numberBetween(1000, 50000),
            'status' => VendorAdvanceRequest::STATUS_REQUESTED,
        ];
    }

    public function paid(): static
    {
        return $this->state(fn () => ['status' => VendorAdvanceRequest::STATUS_FULLY_PAID]);
    }

    public function onHold(): static
    {
        return $this->state(fn () => ['status' => VendorAdvanceRequest::STATUS_ON_HOLD, 'hold_reason' => 'budget_exceeded']);
    }

    public function rejected(): static
    {
        return $this->state(fn () => ['status' => VendorAdvanceRequest::STATUS_REJECTED, 'rejection_reason' => 'high_price']);
    }
}
