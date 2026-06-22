<?php

namespace App\Modules\ChallanEntry\Database\Factories;

use App\Modules\ChallanEntry\Models\Challan;
use App\Modules\VendorMaster\Models\VendorMaster;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Challan>
 */
class ChallanFactory extends Factory
{
    protected $model = Challan::class;

    public function definition(): array
    {
        return [
            'vendor_id' => VendorMaster::factory(),
            'purchase_type' => 'stock',
            'inventory_status' => 'spares_in_transit',
        ];
    }
}
