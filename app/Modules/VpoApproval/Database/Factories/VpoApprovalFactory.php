<?php

namespace App\Modules\VpoApproval\Database\Factories;

use App\Modules\VendorMaster\Models\VendorMaster;
use App\Modules\VpoApproval\Models\VpoApproval;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<VpoApproval>
 */
class VpoApprovalFactory extends Factory
{
    protected $model = VpoApproval::class;

    public function definition(): array
    {
        return [
            'po_approval_type' => 'stock_bulk',
            'vendor_id' => VendorMaster::factory(),
            'status' => VpoApproval::STATUS_SENT,
        ];
    }

    public function fullyApproved(): static
    {
        return $this->state(fn () => ['status' => VpoApproval::STATUS_FULLY_APPROVED, 'approved_at' => now()]);
    }

    public function rejected(): static
    {
        return $this->state(fn () => ['status' => VpoApproval::STATUS_REJECTED, 'rejection_reason' => 'high_price']);
    }
}
