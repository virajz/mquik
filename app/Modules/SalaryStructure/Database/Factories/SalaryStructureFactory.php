<?php

namespace App\Modules\SalaryStructure\Database\Factories;

use App\Modules\EmployeeMaster\Models\EmployeeMaster;
use App\Modules\SalaryStructure\Models\SalaryStructure;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SalaryStructure>
 */
class SalaryStructureFactory extends Factory
{
    protected $model = SalaryStructure::class;

    public function definition(): array
    {
        return [
            'employee_id' => EmployeeMaster::factory(),
            'effective_from' => now()->startOfMonth()->format('Y-m-d'),
            'status' => 'draft',
            'basic_salary' => 20000,
        ];
    }
}
