<?php

namespace App\Modules\VendorPurchaseInquiry\Database\Factories;

use App\Modules\PriorityMaster\Models\PriorityMaster;
use App\Modules\VendorMaster\Models\VendorMaster;
use App\Modules\VendorPurchaseInquiry\Models\VendorPurchaseInquiry;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<VendorPurchaseInquiry>
 */
class VendorPurchaseInquiryFactory extends Factory
{
    protected $model = VendorPurchaseInquiry::class;

    public function definition(): array
    {
        return [
            'vendor_id' => VendorMaster::factory(),
            'inquiry_type' => 'stock_replenishment',
            'priority_id' => PriorityMaster::firstOrCreate(
                ['name' => 'NORMAL'],
                ['code' => 'NRM', 'sort_order' => 10, 'applies_to' => 'both', 'is_active' => true],
            )->id,
            'status' => VendorPurchaseInquiry::STATUS_PENDING,
        ];
    }

    public function inProgress(): static
    {
        return $this->state(fn () => ['status' => VendorPurchaseInquiry::STATUS_IN_PROGRESS]);
    }

    public function completed(): static
    {
        return $this->state(fn () => ['status' => VendorPurchaseInquiry::STATUS_COMPLETED]);
    }

    public function cancelled(): static
    {
        return $this->state(fn () => ['status' => VendorPurchaseInquiry::STATUS_CANCELLED]);
    }
}
