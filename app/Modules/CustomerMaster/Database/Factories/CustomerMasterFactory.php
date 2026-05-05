<?php

namespace App\Modules\CustomerMaster\Database\Factories;

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
            'name' => strtoupper($this->faker->name()),
            'customer_type' => 'walking',
            'phone' => $this->faker->numerify('98########'),
            'alternate_phone' => null,
            'email' => $this->faker->safeEmail(),
            'address' => strtoupper($this->faker->streetAddress()),
            'city' => strtoupper($this->faker->city()),
            'pincode' => $this->faker->numerify('######'),
            'aadhar' => null,
            'pan' => null,
            'date_of_birth' => $this->faker->dateTimeBetween('-65 years', '-18 years')->format('Y-m-d'),
            'notes' => null,
            'is_active' => true,
        ];
    }

    public function loyal(): static
    {
        return $this->state(fn () => ['customer_type' => 'loyal']);
    }

    public function corporate(): static
    {
        return $this->state(fn () => [
            'customer_type' => 'corporate',
            'name' => strtoupper($this->faker->company()),
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
}
