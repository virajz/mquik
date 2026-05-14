<?php

namespace App\Modules\SmartSalary\Database\Factories;

use App\Modules\SmartSalary\Models\SmartSalary;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<SmartSalary>
 */
class SmartSalaryFactory extends Factory
{
    protected $model = SmartSalary::class;

    public function definition(): array
    {
        $name = strtoupper($this->faker->unique()->words(2, true));

        return [
            'key' => Str::upper(Str::slug($name, '_')),
            'name' => $name,
            'category' => $this->faker->randomElement(SmartSalary::categories()),
            'direction' => SmartSalary::DIRECTION_HIGHER,
            'unit' => $this->faker->randomElement(['%', 'count', '₹', 'days', null]),
            'weight' => $this->faker->randomFloat(2, 0, 20),
            'formula' => null,
            'description' => null,
            'is_active' => true,
            'sort_order' => $this->faker->numberBetween(0, 100),
        ];
    }
}
