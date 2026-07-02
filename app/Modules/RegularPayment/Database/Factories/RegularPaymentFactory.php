<?php

namespace App\Modules\RegularPayment\Database\Factories;

use App\Modules\RegularPayment\Models\RegularPayment;
use App\Modules\VendorMaster\Models\VendorMaster;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RegularPayment>
 */
class RegularPaymentFactory extends Factory
{
    protected $model = RegularPayment::class;

    public function definition(): array
    {
        return [
            'vendor_id' => VendorMaster::factory(),
            'status' => 'draft',
            'amount' => 1500,
        ];
    }
}
