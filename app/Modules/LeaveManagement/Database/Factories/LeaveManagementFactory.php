<?php

namespace App\Modules\LeaveManagement\Database\Factories;

use App\Modules\EmployeeMaster\Models\EmployeeMaster;
use App\Modules\LeaveManagement\Models\LeaveManagement;
use App\Modules\LeaveTypeMaster\Models\LeaveTypeMaster;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LeaveManagement>
 */
class LeaveManagementFactory extends Factory
{
    protected $model = LeaveManagement::class;

    public function definition(): array
    {
        // Leave types now come from the editable LeaveTypeMaster — ensure the
        // standard set exists so the type code is always valid.
        $codes = [
            'CL' => 'CASUAL LEAVE', 'SL' => 'SICK LEAVE', 'EL' => 'EARNED LEAVE',
            'ML' => 'MATERNITY LEAVE', 'LWP' => 'LEAVE WITHOUT PAY',
        ];
        foreach ($codes as $code => $name) {
            LeaveTypeMaster::firstOrCreate(['name' => $name], ['code' => $code, 'is_active' => true]);
        }

        $from = $this->faker->dateTimeBetween('now', '+1 month');
        $to = (clone $from)->modify('+1 day');

        return [
            'employee_id' => EmployeeMaster::factory(),
            'leave_type' => $this->faker->randomElement(array_keys($codes)),
            'from_date' => $from->format('Y-m-d'),
            'to_date' => $to->format('Y-m-d'),
            'days_count' => 2,
            'reason' => null,
            'status' => LeaveManagement::STATUS_PENDING,
        ];
    }

    public function approved(): static
    {
        return $this->state(fn () => [
            'status' => LeaveManagement::STATUS_APPROVED,
            'approved_at' => now(),
        ]);
    }

    public function rejected(): static
    {
        return $this->state(fn () => [
            'status' => LeaveManagement::STATUS_REJECTED,
            'approved_at' => now(),
        ]);
    }
}
