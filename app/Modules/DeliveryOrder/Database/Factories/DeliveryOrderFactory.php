<?php

namespace App\Modules\DeliveryOrder\Database\Factories;

use App\Modules\DeliveryOrder\Models\DeliveryOrder;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DeliveryOrder>
 */
class DeliveryOrderFactory extends Factory
{
    protected $model = DeliveryOrder::class;

    public function definition(): array
    {
        return [
            'claim_number' => 'CLM-'.$this->faker->numberBetween(10000, 99999),
            'proforma_amount' => 50000,
            'status' => DeliveryOrder::STATUS_REQUESTED,
            'requested_at' => now(),
        ];
    }

    public function received(): static
    {
        return $this->state(fn () => ['status' => DeliveryOrder::STATUS_RECEIVED, 'do_amount' => 50000, 'do_received_at' => now()]);
    }

    public function mismatch(): static
    {
        return $this->state(fn () => ['status' => DeliveryOrder::STATUS_RECEIVED, 'do_amount' => 42000, 'do_received_at' => now(), 'mismatch_reason' => 'depreciation']);
    }
}
