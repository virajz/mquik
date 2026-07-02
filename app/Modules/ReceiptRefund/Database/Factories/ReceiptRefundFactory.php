<?php

namespace App\Modules\ReceiptRefund\Database\Factories;

use App\Modules\CustomerMaster\Models\CustomerMaster;
use App\Modules\ReceiptRefund\Models\ReceiptRefund;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ReceiptRefund>
 */
class ReceiptRefundFactory extends Factory
{
    protected $model = ReceiptRefund::class;

    public function definition(): array
    {
        return [
            'customer_id' => CustomerMaster::factory(),
            'refund_against' => 'regular_receipt',
            'refund_status' => 'requested',
            'amount' => 500,
        ];
    }
}
