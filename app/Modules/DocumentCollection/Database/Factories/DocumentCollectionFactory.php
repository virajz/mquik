<?php

namespace App\Modules\DocumentCollection\Database\Factories;

use App\Modules\CustomerMaster\Models\CustomerMaster;
use App\Modules\CustomerVehicleMaster\Models\CustomerVehicleMaster;
use App\Modules\DocumentCollection\Models\DocumentCollection;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DocumentCollection>
 */
class DocumentCollectionFactory extends Factory
{
    protected $model = DocumentCollection::class;

    public function definition(): array
    {
        $customer = CustomerMaster::factory();

        return [
            'customer_id' => $customer,
            'customer_vehicle_id' => CustomerVehicleMaster::factory()->for($customer, 'customer'),
            'request_type' => $this->faker->randomElement(['customer', 'insurance_claim']),
            'status' => 'pending',
            'retention' => 'active',
            'requested_at' => now(),
            'entry_at' => now(),
        ];
    }

    public function insuranceClaim(): static
    {
        return $this->state(fn () => ['request_type' => 'insurance_claim']);
    }
}
