<?php

namespace App\Modules\ConsumableApproval\Database\Factories;

use App\Modules\ConsumableApproval\Models\ConsumableApproval;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ConsumableApproval>
 */
class ConsumableApprovalFactory extends Factory
{
    protected $model = ConsumableApproval::class;

    public function definition(): array
    {
        return [
            'consumable_category' => 'paint',
            'priority' => 'normal',
            'status' => ConsumableApproval::STATUS_REQUESTED,
            'requested_at' => now(),
        ];
    }

    public function approved(): static
    {
        return $this->state(fn () => ['status' => ConsumableApproval::STATUS_APPROVED, 'approved_at' => now()]);
    }

    public function rejected(): static
    {
        return $this->state(fn () => ['status' => ConsumableApproval::STATUS_REJECTED, 'rejected_at' => now()]);
    }
}
