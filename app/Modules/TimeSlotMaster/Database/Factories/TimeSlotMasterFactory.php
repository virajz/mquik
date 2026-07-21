<?php

namespace App\Modules\TimeSlotMaster\Database\Factories;

use App\Modules\TimeSlotMaster\Models\TimeSlotMaster;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TimeSlotMaster>
 */
class TimeSlotMasterFactory extends Factory
{
    protected $model = TimeSlotMaster::class;

    public function definition(): array
    {
        // Pick a unique minute-of-day start so neither `name` nor the slot window
        // can collide across rows (1381 distinct values — far past any test's needs).
        $startMinutes = $this->faker->unique()->numberBetween(0, 1380);
        $endMinutes = $startMinutes + 60;

        $start = sprintf('%02d:%02d', intdiv($startMinutes, 60), $startMinutes % 60);
        $end = sprintf('%02d:%02d', intdiv($endMinutes, 60), $endMinutes % 60);

        return [
            'name' => $start.'-'.$end,
            'code' => null,
            'slot_start_time' => $start.':00',
            'slot_end_time' => $end.':00',
            'max_vehicles_per_slot' => 5,
            'buffer_minutes' => 0,
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

    /** A slot with a specific window and capacity — used by capacity tests. */
    public function window(string $start, string $end, int $capacity = 5): static
    {
        return $this->state(fn () => [
            'name' => $start.'-'.$end,
            'slot_start_time' => $start.':00',
            'slot_end_time' => $end.':00',
            'max_vehicles_per_slot' => $capacity,
        ]);
    }
}
