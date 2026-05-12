<?php

namespace App\Modules\VendorMaster\Database\Factories;

use App\Modules\BankMaster\Models\BankMaster;
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
            'phone' => $this->faker->numerify('98########'),
            'alternate_phone' => null,
            'email' => $this->faker->safeEmail(),
            'secondary_email' => null,
            'address' => null,
            'region_id' => null,
            'aadhar' => null,
            'pan' => null,
            'gstin' => null,
            'bank_id' => null,
            'bank_branch' => null,
            'ifsc' => null,
            'account_no' => null,
            'account_holder' => null,
            'credit_days' => 30,
            'credit_limit' => 50000,
            'is_active' => true,
            'notes' => null,
        ];
    }

    /**
     * Auto-attach one vendor type so tests that don't care about types still
     * produce a vendor that satisfies the "at least one type" requirement.
     */
    public function configure(): static
    {
        return $this->afterCreating(function (VendorMaster $vendor) {
            if ($vendor->vendorTypes()->count() === 0) {
                $vendor->vendorTypes()->attach(VendorTypeMaster::factory()->create()->id);
            }
        });
    }

    /**
     * @param  int|VendorTypeMaster|array<int|VendorTypeMaster>  $types
     */
    public function withTypes(int|VendorTypeMaster|array $types): static
    {
        $ids = collect(is_array($types) ? $types : [$types])
            ->map(fn ($t) => $t instanceof VendorTypeMaster ? $t->id : (int) $t)
            ->all();

        return $this->afterCreating(function (VendorMaster $vendor) use ($ids) {
            $vendor->vendorTypes()->sync($ids);
        });
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
        return $this->state(function () {
            $bank = BankMaster::firstOrCreate(
                ['name' => $this->faker->randomElement(['HDFC BANK', 'ICICI BANK', 'SBI', 'AXIS BANK'])],
                ['is_active' => true],
            );

            return [
                'bank_id' => $bank->id,
                'bank_branch' => strtoupper($this->faker->city()),
                'ifsc' => strtoupper($this->faker->bothify('????0######')),
                'account_no' => $this->faker->numerify('###############'),
                'account_holder' => strtoupper($this->faker->company()),
            ];
        });
    }
}
