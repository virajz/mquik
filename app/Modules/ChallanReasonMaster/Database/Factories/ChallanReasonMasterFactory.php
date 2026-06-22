<?php

namespace App\Modules\ChallanReasonMaster\Database\Factories;

use App\Modules\ChallanReasonMaster\Models\ChallanReasonMaster;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ChallanReasonMaster>
 */
class ChallanReasonMasterFactory extends Factory
{
    protected $model = ChallanReasonMaster::class;

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
