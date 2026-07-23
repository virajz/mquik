<?php

namespace App\Modules\InsuranceCompanyMaster\Database\Factories;

use App\Modules\InsuranceCompanyMaster\Models\InsuranceCompanyMaster;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InsuranceCompanyMaster>
 */
class InsuranceCompanyMasterFactory extends Factory
{
    protected $model = InsuranceCompanyMaster::class;

    public function definition(): array
    {
        $name = $this->faker->unique()->company();

        return [
            'name' => strtoupper($name).' INSURANCE',
            'short_name' => strtoupper($this->faker->lexify('???')),
            'gstin' => $this->fakeGstin(),
            'contact_person' => strtoupper($this->faker->name()),
            'phone' => $this->faker->numerify('98########'),
            'email' => $this->faker->safeEmail(),
            'address' => strtoupper($this->faker->address()),
            'notes' => null,
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }

    protected function fakeGstin(): string
    {
        // 2-digit state + 10-char PAN + 1 entity + 1 'Z' + 1 checksum = 15 chars
        return $this->faker->numerify('##')
            .strtoupper($this->faker->lexify('?????'))
            .$this->faker->numerify('####')
            .strtoupper($this->faker->lexify('?'))
            .'1Z'
            .strtoupper($this->faker->lexify('?'));
    }
}
