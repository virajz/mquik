<?php

namespace App\Modules\PaymentRefund\Database\Factories;

use App\Modules\PaymentRefund\Models\PaymentRefund;
use App\Modules\VendorMaster\Models\VendorMaster;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PaymentRefund>
 */
class PaymentRefundFactory extends Factory
{
    protected $model = PaymentRefund::class;

    public function definition(): array
    {
        return [
            'vendor_id' => VendorMaster::factory(),
            'refund_against' => 'advance_payment',
            'refund_type' => 'excess_payment',
            'priority' => 'normal',
            'refund_mode' => 'neft',
            'amount' => $this->faker->numberBetween(1000, 40000),
            'status' => PaymentRefund::STATUS_REQUESTED,
            'requested_at' => now(),
        ];
    }

    public function refunded(): static
    {
        return $this->state(fn () => ['status' => PaymentRefund::STATUS_REFUNDED, 'refunded_at' => now()]);
    }

    public function rejected(): static
    {
        return $this->state(fn () => ['status' => PaymentRefund::STATUS_REJECTED, 'rejected_at' => now(), 'rejection_reason' => 'fitment_issue']);
    }

    public function onHold(): static
    {
        return $this->state(fn () => ['status' => PaymentRefund::STATUS_ON_HOLD, 'hold_reason' => 'vendor_dispute']);
    }
}
