<?php

use App\Modules\CustomerMaster\Models\CustomerMaster;
use App\Modules\CustomerVehicleMaster\Models\CustomerVehicleMaster;
use App\Modules\InsuranceDeductionTypeMaster\Models\InsuranceDeductionTypeMaster;
use App\Modules\Proforma\Livewire\Edit;
use App\Modules\Proforma\Livewire\Index;
use App\Modules\Proforma\Models\Proforma;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(adminUser());
});

it('renders the index page', function () {
    Proforma::factory()->count(2)->create();

    $this->get(route('proforma.index'))
        ->assertOk()
        ->assertSeeLivewire(Index::class);
});

it('requires authentication', function () {
    auth()->logout();
    $this->get(route('proforma.index'))->assertRedirect(route('login'));
});

it('creates a proforma, stamps PF number, and redirects into the editor', function () {
    $customer = CustomerMaster::factory()->create();
    $vehicle = CustomerVehicleMaster::factory()->create(['customer_id' => $customer->id]);

    Livewire::test(Edit::class)
        ->set('customer_vehicle_id', $vehicle->id)   // auto-sets customer
        ->set('warranty_type', 'vendor')
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect();

    $pf = Proforma::firstOrFail();
    expect($pf->proforma_no)->toBe('PF-'.str_pad((string) $pf->id, 5, '0', STR_PAD_LEFT))
        ->and($pf->customer_id)->toBe($customer->id)
        ->and($pf->warranty_type)->toBe('vendor');
});

it('computes profitability (cost vs sell) from line items', function () {
    $pf = Proforma::factory()->create();

    Livewire::test(Edit::class, ['proforma' => $pf])
        ->set('items', [
            ['id' => null, 'line_type' => 'spare', 'spare_id' => null, 'labour_id' => null, 'tax_id' => null, 'rejection_reason_id' => null, 'description' => 'part', 'hsn_code' => null, 'qty' => 2, 'cost_rate' => 100, 'unit_rate' => 150, 'discount_value' => 0, 'tax_percent' => 18, 'sequence_no' => 1],
        ])
        ->call('save')
        ->assertHasNoErrors();

    $pf->refresh();
    // sell 300, cost 200, tax 54, grand 354, profit 100, margin 33.33
    expect((float) $pf->parts_total)->toBe(300.0)
        ->and((float) $pf->cost_total)->toBe(200.0)
        ->and((float) $pf->tax_total)->toBe(54.0)
        ->and((float) $pf->grand_total)->toBe(354.0)
        ->and((float) $pf->profit_total)->toBe(100.0)
        ->and((float) $pf->margin_percent)->toBe(33.33);
});

it('saves insurance deductions', function () {
    $pf = Proforma::factory()->create();
    $type = InsuranceDeductionTypeMaster::factory()->create(['name' => 'SALVAGE CHARGE']);

    Livewire::test(Edit::class, ['proforma' => $pf])
        ->set('deductions', [
            ['id' => null, 'insurance_deduction_type_id' => $type->id, 'amount' => 500, 'notes' => 'scrap value'],
        ])
        ->call('save')
        ->assertHasNoErrors();

    $pf->refresh()->load('deductions');
    expect($pf->deductions)->toHaveCount(1)
        ->and((float) $pf->deductions_total)->toBe(500.0)
        ->and($pf->deductions->first()->insurance_deduction_type_id)->toBe($type->id);
});

it('deletes a proforma from the index', function () {
    $pf = Proforma::factory()->create();

    Livewire::test(Index::class)->call('delete', $pf->id);

    expect(Proforma::find($pf->id))->toBeNull();
});
