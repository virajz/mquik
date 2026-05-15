<?php

namespace App\Modules\Barcode\Database\Factories;

use App\Modules\Barcode\Models\BarcodeLabel;
use App\Modules\SpareMaster\Models\SpareMaster;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BarcodeLabel>
 */
class BarcodeLabelFactory extends Factory
{
    protected $model = BarcodeLabel::class;

    public function definition(): array
    {
        return [
            'spare_id' => SpareMaster::factory(),
            'barcode' => strtoupper(fake()->bothify('MQ#######?')),
            'barcode_type' => BarcodeLabel::TYPE_CODE128,
            'label_size' => '50x25',
            'copies' => 1,
            'is_primary' => false,
        ];
    }

    public function primary(): static
    {
        return $this->state(fn () => ['is_primary' => true]);
    }
}
