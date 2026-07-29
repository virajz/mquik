<?php

namespace App\Modules\GoodsReceipt\Database\Factories;

use App\Modules\GoodsReceipt\Models\GoodsReceipt;
use App\Modules\VendorMaster\Models\VendorMaster;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<GoodsReceipt>
 */
class GoodsReceiptFactory extends Factory
{
    protected $model = GoodsReceipt::class;

    public function definition(): array
    {
        return [
            'vendor_id' => VendorMaster::factory(),
            'goods_receipt_type' => 'against_po',
            'delivery_performance' => 'on_time',
            'status' => GoodsReceipt::STATUS_PENDING,
        ];
    }

    public function verified(): static
    {
        return $this->state(fn () => ['status' => GoodsReceipt::STATUS_VERIFIED]);
    }

    public function mismatchRejected(): static
    {
        return $this->state(fn () => ['status' => GoodsReceipt::STATUS_MISMATCH_REJECTED]);
    }
}
