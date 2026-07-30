<?php

namespace App\Modules\StockCounting\Database\Factories;

use App\Modules\StockCounting\Models\StockCount;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StockCount>
 */
class StockCountFactory extends Factory
{
    protected $model = StockCount::class;

    public function definition(): array
    {
        return [
            'counting_method' => StockCount::METHOD_MANUAL,
            'verification_status' => StockCount::STATUS_PENDING,
            'count_start_date' => now(),
            'count_end_date' => null,
            'team_name' => strtoupper($this->faker->words(2, true)),
            'team_members' => strtoupper($this->faker->name()),
        ];
    }

    public function inProgress(): static
    {
        return $this->state(fn () => ['verification_status' => StockCount::STATUS_IN_PROGRESS]);
    }

    public function completed(): static
    {
        return $this->state(fn () => [
            'verification_status' => StockCount::STATUS_COMPLETED,
            'count_end_date' => now(),
        ]);
    }
}
