<?php

namespace App\Modules\TransmissionTypeMaster\Database\Factories;

use App\Modules\TransmissionTypeMaster\Models\TransmissionTypeMaster;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TransmissionTypeMaster>
 */
class TransmissionTypeMasterFactory extends Factory
{
    protected $model = TransmissionTypeMaster::class;

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
