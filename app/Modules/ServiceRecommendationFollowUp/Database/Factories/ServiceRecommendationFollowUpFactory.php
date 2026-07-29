<?php

namespace App\Modules\ServiceRecommendationFollowUp\Database\Factories;

use App\Modules\ServiceRecommendationFollowUp\Models\ServiceRecommendationFollowUp;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ServiceRecommendationFollowUp>
 */
class ServiceRecommendationFollowUpFactory extends Factory
{
    protected $model = ServiceRecommendationFollowUp::class;

    public function definition(): array
    {
        return [
            'recommended_service' => 'Tyre Replace',
            'recommendation_type' => 'brake',
            'recommendation_reason' => 'worn_out',
            'recommendation_category' => 'preventive_maintenance',
            'priority' => 'normal',
            'status' => ServiceRecommendationFollowUp::STATUS_PENDING,
            'estimated_value' => $this->faker->numberBetween(2000, 25000),
            'customer_retention' => 'active',
            'recommended_at' => now(),
        ];
    }

    public function safety(): static
    {
        return $this->state(fn () => ['recommendation_category' => ServiceRecommendationFollowUp::CATEGORY_SAFETY, 'priority' => 'high']);
    }

    public function converted(): static
    {
        return $this->state(fn () => ['status' => ServiceRecommendationFollowUp::STATUS_CONVERTED]);
    }

    public function lost(): static
    {
        return $this->state(fn () => ['status' => ServiceRecommendationFollowUp::STATUS_LOST, 'lost_reason' => 'serviced_elsewhere']);
    }
}
