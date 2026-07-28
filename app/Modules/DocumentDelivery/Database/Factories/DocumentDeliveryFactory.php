<?php

namespace App\Modules\DocumentDelivery\Database\Factories;

use App\Modules\DocumentDelivery\Models\DocumentDelivery;
use App\Modules\JobCard\Models\JobCard;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DocumentDelivery>
 */
class DocumentDeliveryFactory extends Factory
{
    protected $model = DocumentDelivery::class;

    public function definition(): array
    {
        return [
            'job_card_id' => JobCard::factory(),
            'recipient_type' => 'owner_self',
            'delivery_mode' => 'hand_to_hand',
            'status' => DocumentDelivery::STATUS_PENDING,
        ];
    }

    public function delivered(): static
    {
        return $this->state(fn () => [
            'status' => DocumentDelivery::STATUS_DELIVERED,
            'acknowledgement_type' => 'physical_sign',
            'delivered_at' => now(),
        ]);
    }

    public function inTransit(): static
    {
        return $this->state(fn () => ['status' => DocumentDelivery::STATUS_IN_TRANSIT, 'delivery_mode' => 'courier']);
    }

    public function returned(): static
    {
        return $this->state(fn () => ['status' => DocumentDelivery::STATUS_RETURNED, 'delivery_failure_reason' => 'door_locked']);
    }
}
