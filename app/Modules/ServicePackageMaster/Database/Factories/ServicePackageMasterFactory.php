<?php

namespace App\Modules\ServicePackageMaster\Database\Factories;

use App\Modules\ServicePackageMaster\Models\ServicePackageMaster;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<ServicePackageMaster>
 */
class ServicePackageMasterFactory extends Factory
{
    protected $model = ServicePackageMaster::class;

    public function definition(): array
    {
        $kind = $this->faker->randomElement(['COMBO PACK', 'AMC PACKAGE']);

        return [
            'name' => strtoupper($this->faker->words(2, true)).' '.$kind,
            'code' => 'SP-'.strtoupper(Str::random(6)),
            'description' => strtoupper($this->faker->sentence(6)),
            'is_amc' => $kind === 'AMC PACKAGE',
            'validity_months' => $this->faker->randomElement([12, 24, 36]),
            'validity_km' => $this->faker->randomElement([10000, 20000, 30000]),
            'total_price' => $this->faker->randomFloat(2, 1500, 25000),
            'is_active' => true,
        ];
    }

    public function amc(): static
    {
        return $this->state(fn () => ['is_amc' => true]);
    }
}
