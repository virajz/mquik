<?php

namespace App\Modules\TechnicianFinding\Database\Factories;

use App\Modules\JobCard\Models\JobCard;
use App\Modules\TechnicianFinding\Models\TechnicianFinding;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TechnicianFinding>
 */
class TechnicianFindingFactory extends Factory
{
    protected $model = TechnicianFinding::class;

    public function definition(): array
    {
        return [
            'job_card_id' => JobCard::factory(),
            'finding_type' => TechnicianFinding::TYPE_SPARE,
            'description' => strtoupper($this->faker->unique()->words(3, true)),
            'recommendation' => 'new_issue',
            'status' => TechnicianFinding::STATUS_RECOMMENDED,
        ];
    }

    public function approved(): static
    {
        return $this->state(fn () => ['status' => TechnicianFinding::STATUS_APPROVED]);
    }

    public function labour(): static
    {
        return $this->state(fn () => [
            'finding_type' => TechnicianFinding::TYPE_LABOUR,
            'recommendation' => 'additional_labour',
        ]);
    }
}
