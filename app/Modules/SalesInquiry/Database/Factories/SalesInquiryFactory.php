<?php

namespace App\Modules\SalesInquiry\Database\Factories;

use App\Modules\SalesInquiry\Models\SalesInquiry;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SalesInquiry>
 */
class SalesInquiryFactory extends Factory
{
    protected $model = SalesInquiry::class;

    public function definition(): array
    {
        return [
            'inquiry_type' => 'general_service',
            'inquiry_source' => 'phone_call',
            'priority' => 'normal',
            'status' => SalesInquiry::STATUS_PENDING,
            'estimated_value' => $this->faker->numberBetween(2000, 30000),
            'inquiry_at' => now(),
        ];
    }

    public function quotationSent(): static
    {
        return $this->state(fn () => ['status' => SalesInquiry::STATUS_QUOTATION_SENT, 'quotation_at' => now()]);
    }

    public function converted(): static
    {
        return $this->state(fn () => ['status' => SalesInquiry::STATUS_CONVERTED, 'converted_at' => now()]);
    }

    public function lost(): static
    {
        return $this->state(fn () => ['status' => SalesInquiry::STATUS_LOST, 'lost_reason' => 'price_too_high', 'closed_at' => now()]);
    }
}
