<?php

namespace App\Modules\GateInOut\Database\Factories;

use App\Modules\GateInOut\Models\GateInOut;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<GateInOut>
 */
class GateInOutFactory extends Factory
{
    protected $model = GateInOut::class;

    public function definition(): array
    {
        return [
            'entered_at' => $this->faker->dateTimeBetween('-2 days', '-1 hour'),
            'status' => GateInOut::STATUS_PENDING,
            'registration_no' => 'GJ '.$this->faker->numerify('##').' '.$this->faker->lexify('??').' '.$this->faker->numerify('####'),
            'source' => GateInOut::SOURCE_MANUAL,
        ];
    }

    public function out(): static
    {
        return $this->state(fn () => [
            'exited_at' => now(),
            'outward_type' => 'final_delivery',
            'status' => GateInOut::STATUS_COMPLETED,
        ]);
    }

    public function anpr(): static
    {
        return $this->state(fn () => ['source' => GateInOut::SOURCE_ANPR]);
    }
}
