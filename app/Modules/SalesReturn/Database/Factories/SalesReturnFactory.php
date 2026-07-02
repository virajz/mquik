<?php

namespace App\Modules\SalesReturn\Database\Factories;

use App\Modules\CustomerMaster\Models\CustomerMaster;
use App\Modules\SalesReturn\Models\SalesReturn;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SalesReturn>
 */
class SalesReturnFactory extends Factory
{
    protected $model = SalesReturn::class;

    public function definition(): array
    {
        return [
            'customer_id' => CustomerMaster::factory(),
            'return_type' => 'regular',
            'reference_mode' => 'item_wise',
            'status' => 'draft',
            'refund_status' => 'unpaid',
        ];
    }
}
