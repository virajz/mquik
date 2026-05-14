<?php

namespace App\Modules\LateMemo\Database\Factories;

use App\Modules\EmployeeMaster\Models\EmployeeMaster;
use App\Modules\LateMemo\Models\LateMemo;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LateMemo>
 */
class LateMemoFactory extends Factory
{
    protected $model = LateMemo::class;

    public function definition(): array
    {
        return [
            'employee_id' => EmployeeMaster::factory(),
            'memo_date' => $this->faker->dateTimeBetween('-1 month', 'now')->format('Y-m-d'),
            'late_by_minutes' => $this->faker->numberBetween(10, 90),
            'reason' => null,
            'status' => LateMemo::STATUS_ISSUED,
        ];
    }

    public function acknowledged(): static
    {
        return $this->state(fn () => ['status' => LateMemo::STATUS_ACKNOWLEDGED]);
    }

    public function waived(): static
    {
        return $this->state(fn () => ['status' => LateMemo::STATUS_WAIVED]);
    }
}
