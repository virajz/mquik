<?php

namespace App\Modules\CustomerMaster\Database\Factories;

use App\Modules\CustomerMaster\Models\CustomerAddress;
use App\Modules\CustomerMaster\Models\CustomerMaster;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CustomerAddress>
 */
class CustomerAddressFactory extends Factory
{
    protected $model = CustomerAddress::class;

    public function definition(): array
    {
        return [
            'customer_id' => CustomerMaster::factory(),
            'label' => null,
            'address_line' => strtoupper($this->faker->streetAddress()),
            'region_id' => null,
            'is_primary' => true,
        ];
    }

    public function primary(): static
    {
        return $this->state(fn () => ['is_primary' => true]);
    }

    public function secondary(): static
    {
        return $this->state(fn () => ['is_primary' => false]);
    }

    public function labeled(string $label): static
    {
        return $this->state(fn () => ['label' => strtoupper($label)]);
    }
}
