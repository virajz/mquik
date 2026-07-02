<?php

use App\Modules\CustomerMaster\Models\CustomerMaster;
use App\Modules\CustomerVehicleMaster\Models\CustomerVehicleMaster;
use App\Modules\InsuranceDeductionTypeMaster\Models\InsuranceDeductionTypeMaster;
use App\Modules\Proforma\Models\Proforma;
use App\Modules\RegularSalesInvoice\Livewire\Edit;
use App\Modules\RegularSalesInvoice\Livewire\Index;
use App\Modules\RegularSalesInvoice\Models\RegularSalesInvoice;
use App\Support\FinancialYear;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(adminUser());
});

it('renders the index page', function () {
    RegularSalesInvoice::factory()->count(2)->create();

    $this->get(route('regular-sales-invoice.index'))
        ->assertOk()
        ->assertSeeLivewire(Index::class);
});

it('requires authentication', function () {
    auth()->logout();
    $this->get(route('regular-sales-invoice.index'))->assertRedirect(route('login'));
});

it('creates an invoice, stamps the FY invoice number, and redirects into the editor', function () {
    $customer = CustomerMaster::factory()->create();
    $vehicle = CustomerVehicleMaster::factory()->create(['customer_id' => $customer->id]);

    Livewire::test(Edit::class)
        ->set('customer_vehicle_id', $vehicle->id)   // auto-sets customer
        ->set('invoice_type', 'insurance')
        ->set('warranty_type', 'vendor')
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect();

    $inv = RegularSalesInvoice::firstOrFail();
    $fy = FinancialYear::label();
    expect($inv->invoice_no)->toBe('MQ/'.$fy.'/00001')
        ->and($inv->fy_label)->toBe($fy)
        ->and($inv->customer_id)->toBe($customer->id)
        ->and($inv->invoice_type)->toBe('insurance')
        ->and($inv->warranty_type)->toBe('vendor');
});

it('numbers sequentially within the same financial year', function () {
    RegularSalesInvoice::factory()->create();
    RegularSalesInvoice::factory()->create();

    $fy = FinancialYear::label();
    $invoices = RegularSalesInvoice::orderBy('id')->get();
    expect($invoices[0]->invoice_no)->toBe('MQ/'.$fy.'/00001')
        ->and($invoices[1]->invoice_no)->toBe('MQ/'.$fy.'/00002');
});

it('computes gross margin, tax and balance due from line items', function () {
    $inv = RegularSalesInvoice::factory()->create();

    Livewire::test(Edit::class, ['regularSalesInvoice' => $inv])
        ->set('amount_paid', 100)
        ->set('items', [
            ['id' => null, 'line_type' => 'spare', 'spare_id' => null, 'labour_id' => null, 'uom_id' => null, 'tax_id' => null, 'description' => 'part', 'hsn_code' => null, 'qty' => 2, 'cost_rate' => 100, 'unit_rate' => 150, 'discount_value' => 0, 'tax_percent' => 18, 'sequence_no' => 1],
        ])
        ->call('save')
        ->assertHasNoErrors();

    $inv->refresh();
    // sell 300, cost 200, tax 54, grand 354, profit 100, margin 33.33, balance 354-100=254
    expect((float) $inv->parts_total)->toBe(300.0)
        ->and((float) $inv->cost_total)->toBe(200.0)
        ->and((float) $inv->tax_total)->toBe(54.0)
        ->and((float) $inv->grand_total)->toBe(354.0)
        ->and((float) $inv->profit_total)->toBe(100.0)
        ->and((float) $inv->margin_percent)->toBe(33.33)
        ->and((float) $inv->balance_due)->toBe(254.0);
});

it('saves insurance deductions', function () {
    $inv = RegularSalesInvoice::factory()->create();
    $type = InsuranceDeductionTypeMaster::factory()->create(['name' => 'SALVAGE CHARGE']);

    Livewire::test(Edit::class, ['regularSalesInvoice' => $inv])
        ->set('deductions', [
            ['id' => null, 'insurance_deduction_type_id' => $type->id, 'amount' => 500, 'notes' => 'scrap value'],
        ])
        ->call('save')
        ->assertHasNoErrors();

    $inv->refresh()->load('deductions');
    expect($inv->deductions)->toHaveCount(1)
        ->and((float) $inv->deductions_total)->toBe(500.0);
});

it('stamps invoiced_at when finalized and paid_at when fully paid', function () {
    $inv = RegularSalesInvoice::factory()->create();

    Livewire::test(Edit::class, ['regularSalesInvoice' => $inv])
        ->set('status', 'finalized')
        ->set('payment_status', 'fully_paid')
        ->call('save')
        ->assertHasNoErrors();

    $inv->refresh();
    expect($inv->invoiced_at)->not->toBeNull()
        ->and($inv->paid_at)->not->toBeNull();
});

it('converts a proforma into a fresh invoice, snapshotting header and line items', function () {
    $customer = CustomerMaster::factory()->create();
    $vehicle = CustomerVehicleMaster::factory()->create(['customer_id' => $customer->id]);
    $pf = Proforma::factory()->create([
        'customer_id' => $customer->id,
        'customer_vehicle_id' => $vehicle->id,
        'warranty_type' => 'manufacturer',
    ]);
    $pf->items()->create([
        'line_type' => 'spare', 'description' => 'BRAKE PAD', 'qty' => 1, 'cost_rate' => 80, 'unit_rate' => 120, 'tax_percent' => 18, 'sequence_no' => 1,
    ]);

    $component = Livewire::withQueryParams(['from-proforma' => $pf->id])->test(Edit::class);

    expect($component->get('proforma_id'))->toBe($pf->id)
        ->and($component->get('customer_id'))->toBe($customer->id)
        ->and($component->get('warranty_type'))->toBe('manufacturer')
        ->and($component->get('items'))->toHaveCount(1)
        ->and($component->get('items')[0]['description'])->toBe('BRAKE PAD');
});

it('deletes an invoice from the index', function () {
    $inv = RegularSalesInvoice::factory()->create();

    Livewire::test(Index::class)->call('delete', $inv->id);

    expect(RegularSalesInvoice::find($inv->id))->toBeNull();
});
