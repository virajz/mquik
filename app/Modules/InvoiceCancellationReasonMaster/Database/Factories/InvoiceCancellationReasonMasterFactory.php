<?php

namespace App\Modules\InvoiceCancellationReasonMaster\Database\Factories;

use App\Modules\InvoiceCancellationReasonMaster\Models\InvoiceCancellationReasonMaster;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InvoiceCancellationReasonMaster>
 */
class InvoiceCancellationReasonMasterFactory extends Factory
{
    protected $model = InvoiceCancellationReasonMaster::class;

    public function definition(): array
    {
        return [
            'name' => strtoupper($this->faker->unique()->words(2, true)),
            'code' => null,
            'is_active' => true,
            'notes' => null,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }

    public function withCode(string $code): static
    {
        return $this->state(fn () => ['code' => strtoupper($code)]);
    }
}
