<?php

namespace App\Modules\CounterSalesInvoice\Database\Factories;

use App\Modules\CounterSalesInvoice\Models\CounterSalesInvoice;
use App\Modules\CustomerMaster\Models\CustomerMaster;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CounterSalesInvoice>
 */
class CounterSalesInvoiceFactory extends Factory
{
    protected $model = CounterSalesInvoice::class;

    public function definition(): array
    {
        return [
            'customer_id' => CustomerMaster::factory(),
            'status' => 'draft',
            'payment_status' => 'unpaid',
            'delivery_type' => 'counter_pickup',
        ];
    }
}
