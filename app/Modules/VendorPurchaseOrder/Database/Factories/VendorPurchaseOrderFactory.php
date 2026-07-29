<?php

namespace App\Modules\VendorPurchaseOrder\Database\Factories;

use App\Modules\PriorityMaster\Models\PriorityMaster;
use App\Modules\VendorMaster\Models\VendorMaster;
use App\Modules\VendorPurchaseOrder\Models\VendorPurchaseOrder;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<VendorPurchaseOrder>
 */
class VendorPurchaseOrderFactory extends Factory
{
    protected $model = VendorPurchaseOrder::class;

    public function definition(): array
    {
        return [
            'vendor_id' => VendorMaster::factory(),
            'po_type' => 'stock_replenishment',
            'priority_id' => PriorityMaster::firstOrCreate(
                ['name' => 'NORMAL'],
                ['code' => 'NRM', 'sort_order' => 10, 'applies_to' => 'both', 'is_active' => true],
            )->id,
            'status' => VendorPurchaseOrder::STATUS_PENDING,
            'acknowledgement_status' => VendorPurchaseOrder::ACK_PENDING,
        ];
    }

    public function acknowledged(): static
    {
        return $this->state(fn () => ['status' => VendorPurchaseOrder::STATUS_ACKNOWLEDGED, 'acknowledgement_status' => VendorPurchaseOrder::ACK_ACCEPTED]);
    }

    public function dispatched(): static
    {
        return $this->state(fn () => [
            'status' => VendorPurchaseOrder::STATUS_DISPATCHED,
            'acknowledgement_status' => VendorPurchaseOrder::ACK_ACCEPTED,
            'courier_company' => 'BLUEDART',
            'consignment_no' => (string) $this->faker->numberBetween(100000, 999999),
        ]);
    }

    public function cancelled(): static
    {
        return $this->state(fn () => ['status' => VendorPurchaseOrder::STATUS_CANCELLED]);
    }
}
