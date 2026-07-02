<?php

namespace App\Modules\RegularSalesInvoice\Database\Factories;

use App\Modules\CustomerMaster\Models\CustomerMaster;
use App\Modules\CustomerVehicleMaster\Models\CustomerVehicleMaster;
use App\Modules\RegularSalesInvoice\Models\RegularSalesInvoice;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RegularSalesInvoice>
 */
class RegularSalesInvoiceFactory extends Factory
{
    protected $model = RegularSalesInvoice::class;

    public function definition(): array
    {
        $customer = CustomerMaster::factory()->create();

        return [
            'customer_id' => $customer->id,
            'customer_vehicle_id' => CustomerVehicleMaster::factory()->create(['customer_id' => $customer->id]),
            'invoice_type' => 'regular',
            'status' => 'draft',
            'payment_status' => 'unpaid',
        ];
    }
}
