<?php

namespace App\Modules\AdvancePayment\Database\Factories;

use App\Modules\AdvancePayment\Models\AdvancePayment;
use App\Modules\PaymentModeMaster\Models\PaymentModeMaster;
use App\Modules\VendorMaster\Models\VendorMaster;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AdvancePayment>
 */
class AdvancePaymentFactory extends Factory
{
    protected $model = AdvancePayment::class;

    public function definition(): array
    {
        return [
            'vendor_id' => VendorMaster::factory(),
            'payment_mode_id' => PaymentModeMaster::firstOrCreate(['name' => 'CASH'], ['code' => 'CSH', 'is_active' => true])->id,
            'advance_payment_type' => 'against_request',
            'amount' => $this->faker->numberBetween(1000, 50000),
            'payment_status' => AdvancePayment::STATUS_POSTED,
            'paid_at' => now(),
        ];
    }

    public function cancelled(): static
    {
        return $this->state(fn () => ['payment_status' => AdvancePayment::STATUS_CANCELLED, 'cancellation_reason' => 'wrong_entry']);
    }

    public function reversed(): static
    {
        return $this->state(fn () => ['payment_status' => AdvancePayment::STATUS_REVERSED, 'reversal_reason' => 'duplicate_entry']);
    }

    public function byCheque(): static
    {
        return $this->state(fn () => [
            'cheque_no' => (string) $this->faker->numberBetween(100000, 999999),
            'cheque_status' => 'issued',
        ]);
    }
}
