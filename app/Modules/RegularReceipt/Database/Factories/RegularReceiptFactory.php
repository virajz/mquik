<?php

namespace App\Modules\RegularReceipt\Database\Factories;

use App\Modules\CustomerMaster\Models\CustomerMaster;
use App\Modules\RegularReceipt\Models\RegularReceipt;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RegularReceipt>
 */
class RegularReceiptFactory extends Factory
{
    protected $model = RegularReceipt::class;

    public function definition(): array
    {
        return [
            'customer_id' => CustomerMaster::factory(),
            'status' => 'draft',
            'amount' => 1000,
        ];
    }
}
