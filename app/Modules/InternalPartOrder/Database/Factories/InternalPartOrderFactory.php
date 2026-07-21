<?php

namespace App\Modules\InternalPartOrder\Database\Factories;

use App\Modules\InternalPartOrder\Models\InternalPartOrder;
use App\Modules\PriorityMaster\Models\PriorityMaster;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InternalPartOrder>
 */
class InternalPartOrderFactory extends Factory
{
    protected $model = InternalPartOrder::class;

    public function definition(): array
    {
        return [
            'ipo_type' => 'job_card_requirement',
            'priority_id' => PriorityMaster::firstOrCreate(
                ['name' => 'NORMAL'],
                ['code' => 'NRM', 'sort_order' => 10, 'applies_to' => 'both', 'is_active' => true],
            )->id,
            'status' => 'draft',
            'approval_status' => 'pending',
        ];
    }

    public function requested(): static
    {
        return $this->state(fn () => ['status' => 'requested', 'requested_at' => now()]);
    }
}
