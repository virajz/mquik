<?php

namespace App\Modules\ChallanRejectionReasonMaster\Database\Factories;

use App\Modules\ChallanRejectionReasonMaster\Models\ChallanRejectionReasonMaster;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ChallanRejectionReasonMaster>
 */
class ChallanRejectionReasonMasterFactory extends Factory
{
    protected $model = ChallanRejectionReasonMaster::class;

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
