<?php

namespace App\Modules\PaymentModeMaster\Database\Factories;

use App\Modules\PaymentModeMaster\Models\PaymentModeMaster;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PaymentModeMaster>
 */
class PaymentModeMasterFactory extends Factory
{
    protected $model = PaymentModeMaster::class;

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
}
