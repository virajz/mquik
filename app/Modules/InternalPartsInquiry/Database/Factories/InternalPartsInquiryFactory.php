<?php

namespace App\Modules\InternalPartsInquiry\Database\Factories;

use App\Modules\EmployeeMaster\Models\EmployeeMaster;
use App\Modules\InternalPartsInquiry\Models\InternalPartsInquiry;
use App\Modules\JobCard\Models\JobCard;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InternalPartsInquiry>
 */
class InternalPartsInquiryFactory extends Factory
{
    protected $model = InternalPartsInquiry::class;

    public function definition(): array
    {
        return [
            'job_card_id' => JobCard::factory(),
            'requested_by_employee_id' => EmployeeMaster::factory(),
            'requested_at' => $this->faker->dateTimeBetween('-2 days', 'now'),
            'status' => InternalPartsInquiry::STATUS_OPEN,
        ];
    }

    public function responded(): static
    {
        return $this->state(fn () => ['status' => InternalPartsInquiry::STATUS_RESPONDED]);
    }

    public function closed(): static
    {
        return $this->state(fn () => ['status' => InternalPartsInquiry::STATUS_CLOSED]);
    }
}
