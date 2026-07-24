<?php

namespace App\Modules\LabourMaster\Database\Factories;

use App\Modules\LabourMaster\Models\LabourMaster;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<LabourMaster>
 */
class LabourMasterFactory extends Factory
{
    protected $model = LabourMaster::class;

    public function definition(): array
    {
        $job = strtoupper($this->faker->randomElement([
            'GENERAL SERVICE', 'ENGINE OIL CHANGE', 'BRAKE PAD REPLACEMENT',
            'WHEEL ALIGNMENT', 'AC GAS REFILL', 'BATTERY CHANGE',
            'CLUTCH OVERHAUL', 'BODY DENT REPAIR',
        ]));

        return [
            'name' => $job,
            'labour_code' => 'LB-'.strtoupper(Str::random(6)),
            'description' => strtoupper($this->faker->sentence(4)),
            'hsn_id' => null,
            'rate_before_tax' => $this->faker->randomFloat(2, 100, 3000),
            'is_osl' => false,
            'remark' => null,
            'is_active' => true,
        ];
    }

    public function osl(): static
    {
        return $this->state(fn () => ['is_osl' => true]);
    }
}
