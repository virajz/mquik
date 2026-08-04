<?php

use App\Modules\CustomerMaster\Models\CustomerMaster;
use App\Modules\CustomerVehicleMaster\Models\CustomerVehicleMaster;
use App\Modules\JobCard\Models\JobCard;
use App\Modules\SalesEstimate\Livewire\Edit;
use App\Modules\SalesEstimate\Livewire\Index;
use App\Modules\SalesEstimate\Models\SalesEstimate;
use App\Modules\ServiceTypeMaster\Models\ServiceTypeMaster;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(adminUser());
});

it('renders the index page', function () {
    SalesEstimate::factory()->count(2)->create();

    $this->get(route('sales-estimate.index'))
        ->assertOk()
        ->assertSeeLivewire(Index::class);
});

it('requires authentication', function () {
    auth()->logout();
    $this->get(route('sales-estimate.index'))->assertRedirect(route('login'));
});

it('creates an estimate, stamps SE number, and redirects into the editor', function () {
    $customer = CustomerMaster::factory()->create();
    $vehicle = CustomerVehicleMaster::factory()->create(['customer_id' => $customer->id]);

    Livewire::test(Edit::class)
        ->set('customer_vehicle_id', $vehicle->id)   // auto-sets customer
        ->set('estimate_type', 'additional')
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect();

    $est = SalesEstimate::firstOrFail();
    expect($est->estimate_no)->toBe('SE-'.str_pad((string) $est->id, 5, '0', STR_PAD_LEFT))
        ->and($est->customer_id)->toBe($customer->id)
        ->and($est->estimate_type)->toBe('additional');
});

it('computes totals and insurance pass % from line items', function () {
    $est = SalesEstimate::factory()->create();

    Livewire::test(Edit::class, ['salesEstimate' => $est])
        ->set('items', [
            ['id' => null, 'line_type' => 'spare', 'spare_id' => null, 'labour_id' => null, 'inventory_group_id' => null, 'tax_id' => null, 'description' => 'brake pad', 'hsn_code' => null, 'qty' => 2, 'unit_rate' => 100, 'tax_percent' => 18, 'is_insurance_approved' => false, 'sequence_no' => 1],
            ['id' => null, 'line_type' => 'labour', 'spare_id' => null, 'labour_id' => null, 'inventory_group_id' => null, 'tax_id' => null, 'description' => 'fitting', 'hsn_code' => null, 'qty' => 1, 'unit_rate' => 500, 'tax_percent' => 18, 'is_insurance_approved' => true, 'sequence_no' => 2],
        ])
        ->call('save')
        ->assertHasNoErrors();

    $est->refresh();
    expect((float) $est->parts_total)->toBe(200.0)
        ->and((float) $est->labour_total)->toBe(500.0)
        ->and((float) $est->tax_total)->toBe(126.0)
        ->and((float) $est->grand_total)->toBe(826.0)
        ->and((float) $est->insurance_pass_percent)->toBe(71.43); // 500/700
    expect($est->items)->toHaveCount(2);
});

it('applies a flat discount to the grand total', function () {
    $est = SalesEstimate::factory()->create();

    Livewire::test(Edit::class, ['salesEstimate' => $est])
        ->set('discount_type', 'flat')
        ->set('discount_value', 100)
        ->set('items', [
            ['id' => null, 'line_type' => 'spare', 'spare_id' => null, 'labour_id' => null, 'inventory_group_id' => null, 'tax_id' => null, 'description' => 'part', 'hsn_code' => null, 'qty' => 1, 'unit_rate' => 1000, 'tax_percent' => 0, 'is_insurance_approved' => false, 'sequence_no' => 1],
        ])
        ->call('save')
        ->assertHasNoErrors();

    expect((float) $est->refresh()->discount_total)->toBe(100.0)
        ->and((float) $est->grand_total)->toBe(900.0);
});

it('revises an estimate into a linked draft and marks the source revised', function () {
    $est = SalesEstimate::factory()->create();
    $est->items()->create(['line_type' => 'spare', 'description' => 'PART A', 'qty' => 1, 'unit_rate' => 100, 'tax_percent' => 0, 'line_total' => 100, 'sequence_no' => 1]);

    Livewire::test(Edit::class, ['salesEstimate' => $est])
        ->call('revise')
        ->assertRedirect();

    expect($est->fresh()->status)->toBe('revised');

    $revision = SalesEstimate::where('old_estimate_id', $est->id)->firstOrFail();
    expect($revision->status)->toBe('pending')
        ->and($revision->items()->count())->toBe(1);
});

it('deletes an estimate from the index', function () {
    $est = SalesEstimate::factory()->create();

    Livewire::test(Index::class)->call('delete', $est->id);

    expect(SalesEstimate::find($est->id))->toBeNull();
});

it('derives customer, vehicle, department and service context from the picked job card', function () {
    $jc = JobCard::factory()->create([
        'service_type_id' => ServiceTypeMaster::factory(),
    ]);

    Livewire::test(Edit::class)
        ->set('job_card_id', $jc->id)
        ->assertSet('customer_id', $jc->customer_id)
        ->assertSet('customer_vehicle_id', $jc->customer_vehicle_id)
        ->assertSet('department_id', $jc->workshop_department_id)
        ->assertSet('service_type_id', $jc->service_type_id)
        ->assertSet('advisor_id', $jc->assigned_advisor_id);
});
