<?php

namespace App\Modules\HsnMaster\Database\Factories;

use App\Modules\HsnMaster\Models\HsnMaster;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<HsnMaster>
 */
class HsnMasterFactory extends Factory
{
    protected $model = HsnMaster::class;

    public function definition(): array
    {
        return [
            // Unique 8-digit code — the column is unique and codes are the identity.
            'code' => (string) $this->faker->unique()->numberBetween(10000000, 99999999),
            'name' => strtoupper($this->faker->words(3, true)),
            'kind' => HsnMaster::KIND_HSN,
            'gst_percent' => 18,
            'is_active' => true,
            'notes' => null,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }

    public function sac(): static
    {
        return $this->state(fn () => [
            'kind' => HsnMaster::KIND_SAC,
            'code' => (string) $this->faker->unique()->numberBetween(100000, 999999),
        ]);
    }
}
