<?php

namespace App\Modules\Attendance\Database\Factories;

use App\Modules\Attendance\Models\Attendance;
use App\Modules\EmployeeMaster\Models\EmployeeMaster;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Attendance>
 */
class AttendanceFactory extends Factory
{
    protected $model = Attendance::class;

    public function definition(): array
    {
        return [
            'employee_id' => EmployeeMaster::factory(),
            'punched_at' => $this->faker->dateTimeBetween('-30 days', 'now'),
            'type' => $this->faker->randomElement([Attendance::TYPE_IN, Attendance::TYPE_OUT]),
            'notes' => null,
        ];
    }

    public function punchIn(): static
    {
        return $this->state(fn () => ['type' => Attendance::TYPE_IN]);
    }

    public function punchOut(): static
    {
        return $this->state(fn () => ['type' => Attendance::TYPE_OUT]);
    }
}
