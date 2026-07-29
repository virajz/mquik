<?php

namespace App\Modules\OutsideLabourCreditNote\Database\Factories;

use App\Modules\OutsideLabourCreditNote\Models\OutsideLabourCreditNote;
use App\Modules\VendorMaster\Models\VendorMaster;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OutsideLabourCreditNote>
 */
class OutsideLabourCreditNoteFactory extends Factory
{
    protected $model = OutsideLabourCreditNote::class;

    public function definition(): array
    {
        return [
            'vendor_id' => VendorMaster::factory(),
            'note_type' => 'credit_note',
            'invoice_type' => 'tax_credit_note',
            'return_reason' => 'workmanship_failure',
            'commercial_settlement' => 'full',
            'status' => OutsideLabourCreditNote::STATUS_POSTED,
            'amount' => $this->faker->numberBetween(500, 20000),
        ];
    }

    public function debitNote(): static
    {
        return $this->state(fn () => ['note_type' => 'debit_note']);
    }

    public function cancelled(): static
    {
        return $this->state(fn () => ['status' => OutsideLabourCreditNote::STATUS_CANCELLED]);
    }
}
