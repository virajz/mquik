<?php

namespace App\Modules\InvoiceCorrection\Database\Factories;

use App\Modules\InvoiceCorrection\Models\InvoiceCorrection;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InvoiceCorrection>
 */
class InvoiceCorrectionFactory extends Factory
{
    protected $model = InvoiceCorrection::class;

    public function definition(): array
    {
        return [
            'correction_request_type' => 'spares_rate',
            'correction_reason' => 'data_entry_mistake',
            'priority' => 'normal',
            'billing_action' => 'correct_existing',
            'invoice_type' => 'regular',
            'invoice_reference' => 'MQ/26-27/'.$this->faker->numberBetween(10000, 99999),
            'status' => InvoiceCorrection::STATUS_REQUESTED,
            'requested_at' => now(),
        ];
    }

    public function approved(): static
    {
        return $this->state(fn () => ['status' => InvoiceCorrection::STATUS_APPROVED, 'approved_at' => now()]);
    }

    public function corrected(): static
    {
        return $this->state(fn () => ['status' => InvoiceCorrection::STATUS_CORRECTED, 'approved_at' => now(), 'corrected_at' => now()]);
    }

    public function rejected(): static
    {
        return $this->state(fn () => ['status' => InvoiceCorrection::STATUS_REJECTED]);
    }
}
