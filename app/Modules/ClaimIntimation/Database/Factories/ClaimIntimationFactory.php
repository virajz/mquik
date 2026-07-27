<?php

namespace App\Modules\ClaimIntimation\Database\Factories;

use App\Modules\ClaimIntimation\Models\ClaimIntimation;
use App\Modules\JobCard\Models\JobCard;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ClaimIntimation>
 */
class ClaimIntimationFactory extends Factory
{
    protected $model = ClaimIntimation::class;

    public function definition(): array
    {
        return [
            'job_card_id' => JobCard::factory(),
            'damage_nature' => 'accident',
            'status' => ClaimIntimation::STATUS_PENDING,
        ];
    }

    public function intimated(): static
    {
        return $this->state(fn () => [
            'status' => ClaimIntimation::STATUS_INTIMATED,
            'intimated_at' => now(),
            'claim_no' => 'CLM'.$this->faker->numerify('######'),
        ]);
    }

    public function cancelled(): static
    {
        return $this->state(fn () => ['status' => ClaimIntimation::STATUS_CANCELLED]);
    }
}
