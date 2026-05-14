<?php

namespace App\Modules\Payroll\Database\Factories;

use App\Modules\EmployeeMaster\Models\EmployeeMaster;
use App\Modules\Payroll\Models\Payroll;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Payroll>
 */
class PayrollFactory extends Factory
{
    protected $model = Payroll::class;

    public function definition(): array
    {
        $basic = $this->faker->numberBetween(15000, 80000);
        $hra = (int) round($basic * 0.4);
        $da = (int) round($basic * 0.1);
        $allowances = $this->faker->numberBetween(0, 5000);
        $deductions = $this->faker->numberBetween(1000, 8000);
        $gross = $basic + $hra + $da + $allowances;
        $net = $gross - $deductions;

        return [
            'employee_id' => EmployeeMaster::factory(),
            'period_year' => 2026,
            'period_month' => $this->faker->numberBetween(1, 12),
            'basic_amount' => $basic,
            'hra_amount' => $hra,
            'da_amount' => $da,
            'allowances_amount' => $allowances,
            'deductions_amount' => $deductions,
            'gross_amount' => $gross,
            'net_amount' => $net,
            'status' => Payroll::STATUS_DRAFT,
        ];
    }

    public function finalized(): static
    {
        return $this->state(fn () => ['status' => Payroll::STATUS_FINALIZED]);
    }

    public function paid(): static
    {
        return $this->state(fn () => [
            'status' => Payroll::STATUS_PAID,
            'payment_date' => now()->toDateString(),
        ]);
    }
}
