<?php

namespace App\Modules\VendorMaster\Database\Factories;

use App\Modules\VendorMaster\Models\VendorMaster;
use App\Modules\VendorMaster\Models\VendorTerm;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<VendorTerm>
 */
class VendorTermFactory extends Factory
{
    protected $model = VendorTerm::class;

    public function definition(): array
    {
        return [
            'vendor_id' => VendorMaster::factory(),
            'name' => 'Payment Term',
            'value' => 'Net 30 days',
            'sort_order' => 0,
        ];
    }
}
