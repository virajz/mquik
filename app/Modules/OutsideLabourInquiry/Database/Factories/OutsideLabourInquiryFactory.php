<?php

namespace App\Modules\OutsideLabourInquiry\Database\Factories;

use App\Modules\OutsideLabourInquiry\Models\OutsideLabourInquiry;
use App\Modules\PriorityMaster\Models\PriorityMaster;
use App\Modules\ServiceSpecialistMaster\Models\ServiceSpecialistMaster;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OutsideLabourInquiry>
 */
class OutsideLabourInquiryFactory extends Factory
{
    protected $model = OutsideLabourInquiry::class;

    public function definition(): array
    {
        return [
            'inquiry_type_id' => ServiceSpecialistMaster::firstOrCreate(
                ['name' => 'DENTING'],
                ['code' => 'DNT', 'is_active' => true],
            )->id,
            'priority_id' => PriorityMaster::firstOrCreate(
                ['name' => 'NORMAL'],
                ['code' => 'NRM', 'sort_order' => 10, 'applies_to' => 'both', 'is_active' => true],
            )->id,
            'status' => OutsideLabourInquiry::STATUS_RESPONSE_PENDING,
        ];
    }

    public function responded(): static
    {
        return $this->state(fn () => ['status' => OutsideLabourInquiry::STATUS_FULLY_RESPONDED]);
    }

    public function issued(): static
    {
        return $this->state(fn () => ['status' => OutsideLabourInquiry::STATUS_WORK_ORDER_ISSUED]);
    }

    public function rejected(): static
    {
        return $this->state(fn () => ['status' => OutsideLabourInquiry::STATUS_REJECTED]);
    }
}
