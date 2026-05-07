<?php

namespace App\Modules\TaxMaster\Database\Factories;

use App\Modules\TaxMaster\Models\TaxMaster;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TaxMaster>
 */
class TaxMasterFactory extends Factory
{
    protected $model = TaxMaster::class;

    public function definition(): array
    {
        $rate = $this->faker->randomElement(['0.00', '5.00', '12.00', '18.00', '28.00']);

        return [
            'name' => 'GST '.rtrim(rtrim($rate, '0'), '.').'%',
            'code' => 'GST'.$this->faker->unique()->numerify('####'),
            'hsn_sac' => null,
            'gst_percent' => $rate,
            'cess_percent' => '0.00',
            'is_active' => true,
            'notes' => null,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }

    public function withHsn(string $hsn, string $rate = '18.00'): static
    {
        return $this->state(fn () => ['hsn_sac' => $hsn, 'gst_percent' => $rate]);
    }
}
