<?php

namespace App\Modules\ExcessStockApproval\Database\Factories;

use App\Modules\ExcessStockApproval\Models\ExcessStockApproval;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ExcessStockApproval>
 */
class ExcessStockApprovalFactory extends Factory
{
    protected $model = ExcessStockApproval::class;

    public function definition(): array
    {
        return [
            'excess_stock_reason' => 'excess_purchase',
            'priority' => 'normal',
            'status' => ExcessStockApproval::STATUS_REQUESTED,
            'requested_at' => now(),
        ];
    }

    public function approved(): static
    {
        return $this->state(fn () => ['status' => ExcessStockApproval::STATUS_APPROVED, 'approved_at' => now()]);
    }

    public function rejected(): static
    {
        return $this->state(fn () => ['status' => ExcessStockApproval::STATUS_REJECTED, 'rejected_at' => now()]);
    }

    public function deadStock(): static
    {
        return $this->state(fn () => ['excess_stock_reason' => 'return_window_expired']);
    }
}
