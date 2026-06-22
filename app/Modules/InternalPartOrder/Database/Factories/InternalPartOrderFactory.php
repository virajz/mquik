<?php

namespace App\Modules\InternalPartOrder\Database\Factories;

use App\Modules\InternalPartOrder\Models\InternalPartOrder;
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
            'order_priority' => 'normal',
            'status' => 'draft',
            'approval_status' => 'pending',
        ];
    }

    public function requested(): static
    {
        return $this->state(fn () => ['status' => 'requested', 'requested_at' => now()]);
    }
}
