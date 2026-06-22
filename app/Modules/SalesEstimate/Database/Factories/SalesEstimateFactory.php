<?php

namespace App\Modules\SalesEstimate\Database\Factories;

use App\Modules\CustomerMaster\Models\CustomerMaster;
use App\Modules\CustomerVehicleMaster\Models\CustomerVehicleMaster;
use App\Modules\SalesEstimate\Models\SalesEstimate;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SalesEstimate>
 */
class SalesEstimateFactory extends Factory
{
    protected $model = SalesEstimate::class;

    public function definition(): array
    {
        $customer = CustomerMaster::factory()->create();

        return [
            'customer_id' => $customer->id,
            'customer_vehicle_id' => CustomerVehicleMaster::factory()->create(['customer_id' => $customer->id]),
            'estimate_type' => 'before',
            'parts_category' => 'any',
            'status' => 'pending',
            'labour_price_tier' => 'retail',
        ];
    }
}
