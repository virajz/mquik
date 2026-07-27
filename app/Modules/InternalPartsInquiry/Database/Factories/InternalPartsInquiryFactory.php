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
            'inquiry_type' => 'against_job_card',
            'status' => InternalPartsInquiry::STATUS_PENDING,
        ];
    }

    public function inProgress(): static
    {
        return $this->state(fn () => ['status' => InternalPartsInquiry::STATUS_IN_PROGRESS]);
    }

    public function fullyAvailable(): static
    {
        return $this->state(fn () => ['status' => InternalPartsInquiry::STATUS_FULLY_AVAILABLE]);
    }

    public function ordered(): static
    {
        return $this->state(fn () => ['status' => InternalPartsInquiry::STATUS_ORDERED]);
    }

    public function completed(): static
    {
        return $this->state(fn () => ['status' => InternalPartsInquiry::STATUS_COMPLETED]);
    }

    public function cancelled(): static
    {
        return $this->state(fn () => ['status' => InternalPartsInquiry::STATUS_CANCELLED]);
    }
}
