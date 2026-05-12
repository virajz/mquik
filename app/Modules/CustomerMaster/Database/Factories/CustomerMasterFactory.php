<?php

namespace App\Modules\CustomerMaster\Database\Factories;

use App\Modules\BusinessTypeMaster\Models\BusinessTypeMaster;
use App\Modules\CustomerMaster\Models\CustomerAddress;
use App\Modules\CustomerMaster\Models\CustomerMaster;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CustomerMaster>
 */
class CustomerMasterFactory extends Factory
{
    protected $model = CustomerMaster::class;

    public function definition(): array
    {
        return [
            'first_name' => strtoupper($this->faker->firstName()),
            'middle_name' => null,
            'last_name' => strtoupper($this->faker->lastName()),
            'business_type_id' => BusinessTypeMaster::firstOrCreate(['name' => 'WALKING'], ['is_active' => true])->id,
            'referred_by_customer_id' => null,
            'phone' => $this->faker->numerify('98########'),
            'alternate_phone' => null,
            'email' => $this->faker->safeEmail(),
            'aadhar' => null,
            'pan' => null,
            'date_of_birth' => $this->faker->dateTimeBetween('-65 years', '-18 years')->format('Y-m-d'),
            'notes' => null,
            'is_active' => true,
        ];
    }

    public function loyal(): static
    {
        return $this->state(fn () => [
            'business_type_id' => BusinessTypeMaster::firstOrCreate(['name' => 'LOYAL'], ['is_active' => true])->id,
        ]);
    }

    public function corporate(): static
    {
        return $this->state(fn () => [
            'business_type_id' => BusinessTypeMaster::firstOrCreate(['name' => 'CORPORATE'], ['is_active' => true])->id,
            'first_name' => strtoupper($this->faker->company()),
            'middle_name' => null,
            'last_name' => null,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }

    public function withKyc(): static
    {
        return $this->state(fn () => [
            'aadhar' => $this->faker->unique()->numerify('############'),
            'pan' => strtoupper($this->faker->bothify('?????####?')),
        ]);
    }

    public function withAddress(?string $label = null): static
    {
        return $this->afterCreating(function (CustomerMaster $customer) use ($label) {
            CustomerAddress::factory()->primary()->create([
                'customer_id' => $customer->id,
                'label' => $label,
            ]);
        });
    }
}
