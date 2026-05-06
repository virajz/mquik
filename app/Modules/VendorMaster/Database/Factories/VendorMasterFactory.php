<?php

namespace App\Modules\VendorMaster\Database\Factories;

use App\Modules\VendorMaster\Models\VendorMaster;
use App\Modules\VendorTypeMaster\Models\VendorTypeMaster;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<VendorMaster>
 */
class VendorMasterFactory extends Factory
{
    protected $model = VendorMaster::class;

    public function definition(): array
    {
        return [
            'vendor_code' => 'VND-'.$this->faker->unique()->numerify('#####'),
            'name' => strtoupper($this->faker->company()),
            'vendor_type_id' => VendorTypeMaster::factory(),
            'phone' => $this->faker->numerify('98########'),
            'alternate_phone' => null,
            'email' => $this->faker->safeEmail(),
            'address' => null,
            'city' => strtoupper($this->faker->city()),
            'state' => strtoupper($this->faker->randomElement(['GUJARAT', 'MAHARASHTRA', 'KARNATAKA', 'DELHI', 'RAJASTHAN'])),
            'pincode' => $this->faker->numerify('######'),
            // KYC + banking left null by default
            'pan' => null,
            'gstin' => null,
            'bank_name' => null,
            'bank_branch' => null,
            'ifsc' => null,
            'account_no' => null,
            'account_holder' => null,
            'credit_days' => 30,
            'credit_limit' => 50000,
            'payment_terms' => null,
            'is_active' => true,
            'notes' => null,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }

    public function withKyc(): static
    {
        return $this->state(fn () => [
            'pan' => strtoupper($this->faker->bothify('?????####?')),
            'gstin' => strtoupper($this->faker->bothify('##?????####?#Z?')),
        ]);
    }

    public function withBanking(): static
    {
        return $this->state(fn () => [
            'bank_name' => strtoupper($this->faker->randomElement(['HDFC BANK', 'ICICI BANK', 'SBI', 'AXIS BANK'])),
            'bank_branch' => strtoupper($this->faker->city()),
            'ifsc' => strtoupper($this->faker->bothify('????0######')),
            'account_no' => $this->faker->numerify('###############'),
            'account_holder' => strtoupper($this->faker->company()),
        ]);
    }
}
