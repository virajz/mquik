<?php

namespace App\Modules\CompanyMaster\Database\Factories;

use App\Modules\CompanyMaster\Models\CompanyMaster;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CompanyMaster>
 */
class CompanyMasterFactory extends Factory
{
    protected $model = CompanyMaster::class;

    public function definition(): array
    {
        return [
            'legal_name' => strtoupper($this->faker->company()).' PVT LTD',
            'trade_name' => strtoupper($this->faker->company()),
            'code' => strtoupper($this->faker->lexify('????')),
            'gstin' => null,
            'pan' => null,
            'cin' => null,
            'address' => strtoupper($this->faker->streetAddress()),
            'pincode' => '380015',
            'phone' => '9876543210',
            'email' => $this->faker->safeEmail(),
            'website' => 'https://example.com',
            'invoice_footer' => 'THANK YOU FOR YOUR BUSINESS.',
            'is_active' => true,
        ];
    }
}
