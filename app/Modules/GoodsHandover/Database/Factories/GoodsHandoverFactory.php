<?php

namespace App\Modules\GoodsHandover\Database\Factories;

use App\Modules\EmployeeMaster\Models\EmployeeMaster;
use App\Modules\GoodsHandover\Models\GoodsHandover;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<GoodsHandover>
 */
class GoodsHandoverFactory extends Factory
{
    protected $model = GoodsHandover::class;

    public function definition(): array
    {
        return [
            'handover_by_id' => EmployeeMaster::factory(),
            'received_by_id' => EmployeeMaster::factory(),
            'status' => GoodsHandover::STATUS_RECEIVED,
        ];
    }

    public function withReturn(): static
    {
        return $this->state(fn () => ['material_return_status' => 'partial_return', 'return_reason' => 'excess_issue']);
    }

    public function verified(): static
    {
        return $this->state(fn () => ['status' => GoodsHandover::STATUS_VERIFIED]);
    }
}
