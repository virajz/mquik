<?php

namespace App\Modules\SpareMaster\Database\Factories;

use App\Modules\SpareMaster\Models\SpareMaster;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<SpareMaster>
 */
class SpareMasterFactory extends Factory
{
    protected $model = SpareMaster::class;

    public function definition(): array
    {
        $part = strtoupper($this->faker->randomElement([
            'BRAKE PAD', 'AIR FILTER', 'OIL FILTER', 'SPARK PLUG',
            'CLUTCH PLATE', 'WIPER BLADE', 'BATTERY', 'HEADLIGHT',
        ]));

        return [
            'name' => $part.' '.strtoupper(Str::random(4)),
            'spare_code' => 'SP-'.strtoupper(Str::random(6)),
            'description' => strtoupper($this->faker->sentence(4)),
            'hsn_code' => (string) $this->faker->numberBetween(8000, 8999),
            'mrp' => $this->faker->randomFloat(2, 100, 5000),
            'rate_before_tax' => $this->faker->randomFloat(2, 50, 5000),
            'min_qty' => $this->faker->numberBetween(1, 5),
            'max_qty' => $this->faker->numberBetween(20, 100),
            'barcode_type' => $this->faker->randomElement(['EAN-13', 'CODE-128', 'QR', null]),
            'location' => 'RACK-'.strtoupper(Str::random(2)).'-'.$this->faker->numberBetween(1, 99),
            'is_tyre' => false,
            'remark' => null,
            'is_active' => true,
        ];
    }

    public function tyre(): static
    {
        return $this->state(fn () => [
            'name' => strtoupper($this->faker->randomElement(['MRF ZAPPER', 'APOLLO ALNAC', 'CEAT MILAZE', 'BRIDGESTONE TURANZA'])),
            'is_tyre' => true,
            'tyre_dimension' => '195/65 R15',
            'rim_size' => '15',
            'load_speed_index' => '91H',
            'tread_pattern' => strtoupper($this->faker->randomElement(['HIGHWAY', 'OFF-ROAD', 'TOURING', 'PERFORMANCE'])),
        ]);
    }
}
