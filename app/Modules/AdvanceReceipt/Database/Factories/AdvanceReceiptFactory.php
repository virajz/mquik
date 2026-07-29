<?php

namespace App\Modules\AdvanceReceipt\Database\Factories;

use App\Modules\AdvanceReceipt\Models\AdvanceReceipt;
use App\Modules\JobCard\Models\JobCard;
use App\Modules\PaymentModeMaster\Models\PaymentModeMaster;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AdvanceReceipt>
 */
class AdvanceReceiptFactory extends Factory
{
    protected $model = AdvanceReceipt::class;

    public function definition(): array
    {
        return [
            'job_card_id' => JobCard::factory(),
            'payment_mode_id' => PaymentModeMaster::firstOrCreate(['name' => 'CASH'], ['code' => 'CSH', 'is_active' => true])->id,
            'amount' => $this->faker->numberBetween(500, 20000),
            'payment_status' => AdvanceReceipt::STATUS_FULLY_RECEIVED,
            'received_at' => now(),
        ];
    }

    public function partiallyReceived(): static
    {
        return $this->state(fn () => ['payment_status' => AdvanceReceipt::STATUS_PARTIALLY_RECEIVED]);
    }

    public function cancelled(): static
    {
        return $this->state(fn () => [
            'payment_status' => AdvanceReceipt::STATUS_CANCELLED,
        ]);
    }

    public function byCheque(): static
    {
        return $this->state(fn () => [
            'cheque_no' => (string) $this->faker->numberBetween(100000, 999999),
            'cheque_status' => 'received',
        ]);
    }
}
