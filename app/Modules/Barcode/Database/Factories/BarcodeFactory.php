<?php

namespace App\Modules\Barcode\Database\Factories;

use App\Modules\Barcode\Models\Barcode;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Barcode>
 */
class BarcodeFactory extends Factory
{
    protected $model = Barcode::class;

    public function definition(): array
    {
        return [
            'name' => strtoupper($this->faker->words(2, true)),
            'description' => $this->faker->sentence(),
        ];
    }
}
