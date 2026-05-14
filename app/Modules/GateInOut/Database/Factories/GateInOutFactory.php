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
            'direction' => GateInOut::DIRECTION_IN,
            'gated_at' => $this->faker->dateTimeBetween('-2 days', 'now'),
            'registration_no' => 'GJ '.$this->faker->numerify('##').' '.$this->faker->lexify('??').' '.$this->faker->numerify('####'),
            'source' => GateInOut::SOURCE_MANUAL,
        ];
    }

    public function out(): static
    {
        return $this->state(fn () => ['direction' => GateInOut::DIRECTION_OUT]);
    }

    public function anpr(): static
    {
        return $this->state(fn () => ['source' => GateInOut::SOURCE_ANPR]);
    }
}
