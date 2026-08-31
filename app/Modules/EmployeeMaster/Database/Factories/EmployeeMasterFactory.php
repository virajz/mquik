<?php

namespace App\Modules\EmployeeMaster\Database\Factories;

use App\Modules\DepartmentMaster\Models\DepartmentMaster;
use App\Modules\DesignationMaster\Models\DesignationMaster;
use App\Modules\EmployeeMaster\Models\EmployeeMaster;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EmployeeMaster>
 */
class EmployeeMasterFactory extends Factory
{
    protected $model = EmployeeMaster::class;

    public function definition(): array
    {
        return [
            'employee_code' => 'EMP-'.$this->faker->unique()->numerify('#####'),
            'name' => strtoupper($this->faker->name()),
            'gender' => $this->faker->randomElement(['male', 'female']),
            'date_of_birth' => $this->faker->dateTimeBetween('-60 years', '-22 years')->format('Y-m-d'),
            'phone' => $this->faker->numerify('98########'),
            'email' => $this->faker->safeEmail(),
            'address' => strtoupper($this->faker->streetAddress()),
            'city' => strtoupper($this->faker->city()),
            'pincode' => $this->faker->numerify('######'),
            'aadhar' => null,
            'pan' => null,
            'designation_id' => DesignationMaster::factory(),
            'department_id' => DepartmentMaster::factory(),
            'joining_date' => $this->faker->dateTimeBetween('-10 years', 'now')->format('Y-m-d'),
            'exit_date' => null,
            'bank_name' => strtoupper($this->faker->randomElement(['HDFC BANK', 'ICICI BANK', 'SBI', 'AXIS BANK'])),
            'bank_branch' => strtoupper($this->faker->city()),
            'ifsc' => strtoupper($this->faker->bothify('????0######')),
            'account_no' => $this->faker->numerify('###############'),
            'is_active' => true,
            'notes' => null,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['is_active' => false, 'exit_date' => now()->format('Y-m-d')]);
    }

    public function technician(): static
    {
        return $this->state(fn () => [
            'designation_id' => DesignationMaster::firstOrCreate(['name' => 'TECHNICIAN'], ['is_active' => true])->id,
            'department_id' => DepartmentMaster::firstOrCreate(['name' => 'SERVICE'], ['is_active' => true])->id,
        ]);
    }

    public function floorIncharge(): static
    {
        return $this->state(fn () => [
            'designation_id' => DesignationMaster::firstOrCreate(['name' => 'FLOOR INCHARGE'], ['is_active' => true])->id,
            'department_id' => DepartmentMaster::firstOrCreate(['name' => 'SERVICE'], ['is_active' => true])->id,
        ]);
    }

    public function advisor(): static
    {
        return $this->state(fn () => [
            'designation_id' => DesignationMaster::firstOrCreate(['name' => 'MECHANICAL ADVISOR'], ['is_active' => true])->id,
            'department_id' => DepartmentMaster::firstOrCreate(['name' => 'SERVICE'], ['is_active' => true])->id,
        ]);
    }
}
