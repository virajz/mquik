<?php

namespace App\Modules\Proforma\Database\Factories;

use App\Modules\CustomerMaster\Models\CustomerMaster;
use App\Modules\CustomerVehicleMaster\Models\CustomerVehicleMaster;
use App\Modules\Proforma\Models\Proforma;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Proforma>
 */
class ProformaFactory extends Factory
{
    protected $model = Proforma::class;

    public function definition(): array
    {
        $customer = CustomerMaster::factory()->create();

        return [
            'customer_id' => $customer->id,
            'customer_vehicle_id' => CustomerVehicleMaster::factory()->create(['customer_id' => $customer->id]),
            'status' => 'draft',
            'approval_status' => 'pending',
        ];
    }
}
