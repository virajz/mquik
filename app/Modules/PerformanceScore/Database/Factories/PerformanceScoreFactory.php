<?php

namespace App\Modules\PerformanceScore\Database\Factories;

use App\Modules\EmployeeMaster\Models\EmployeeMaster;
use App\Modules\PerformanceScore\Models\PerformanceScore;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PerformanceScore>
 */
class PerformanceScoreFactory extends Factory
{
    protected $model = PerformanceScore::class;

    public function definition(): array
    {
        return [
            'employee_id' => EmployeeMaster::factory(),
            'period_year' => 2026,
            'period_month' => $this->faker->numberBetween(1, 12),
            'status' => 'draft',
        ];
    }
}
