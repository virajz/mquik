<?php

use App\Modules\AdvancePayment\Livewire\Edit;
use App\Modules\AdvancePayment\Livewire\Index;
use App\Modules\AdvancePayment\Models\AdvancePayment;
use App\Modules\PaymentModeMaster\Models\PaymentModeMaster;
use App\Modules\VendorMaster\Models\VendorMaster;
use App\Support\FinancialYear;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(adminUser());
    $this->cash = PaymentModeMaster::firstOrCreate(['name' => 'CASH'], ['code' => 'CSH', 'is_active' => true]);
});

it('renders the index page', function () {
    AdvancePayment::factory()->count(3)->create();

    $this->get(route('advance-payment.index'))->assertOk()->assertSeeLivewire(Index::class);
});

it('renders the create page', function () {
    $this->get(route('advance-payment.create'))->assertOk()->assertSeeLivewire(Edit::class);
});

it('records a payment with an MQ/AP FY series number and redirects', function () {
    $vendor = VendorMaster::factory()->create();

    Livewire::test(Edit::class)
        ->set('vendor_id', $vendor->id)
        ->set('payment_mode_id', $this->cash->id)
        ->set('advance_payment_type', 'against_request')
        ->set('amount', 15000)
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect(route('advance-payment.index'));

    $p = AdvancePayment::first();
    $fy = FinancialYear::label($p->created_at);
    expect($p->payment_no)->toBe('MQ/AP/'.$fy.'/00001')
        ->and($p->fy_label)->toBe($fy)
        ->and($p->vendor_id)->toBe($vendor->id)
        ->and((float) $p->amount)->toBe(15000.0);
});

it('increments the FY sequence per financial year', function () {
    $vendor = VendorMaster::factory()->create();

    AdvancePayment::factory()->create();
    AdvancePayment::factory()->create();

    $fy = FinancialYear::label(now());
    $second = AdvancePayment::orderBy('id')->get();
    expect($second[0]->payment_no)->toBe('MQ/AP/'.$fy.'/00001')
        ->and($second[1]->payment_no)->toBe('MQ/AP/'.$fy.'/00002');
});

it('requires a vendor', function () {
    Livewire::test(Edit::class)
        ->set('vendor_id', null)
        ->set('payment_mode_id', $this->cash->id)
        ->set('amount', 100)
        ->call('save')
        ->assertHasErrors(['vendor_id']);
});

it('requires a payment mode and a positive amount', function () {
    $vendor = VendorMaster::factory()->create();

    Livewire::test(Edit::class)
        ->set('vendor_id', $vendor->id)
        ->set('payment_mode_id', null)
        ->set('amount', 0)
        ->call('save')
        ->assertHasErrors(['payment_mode_id', 'amount']);
});

it('requires a reversal reason when reversed', function () {
    $vendor = VendorMaster::factory()->create();

    Livewire::test(Edit::class)
        ->set('vendor_id', $vendor->id)
        ->set('payment_mode_id', $this->cash->id)
        ->set('amount', 100)
        ->set('payment_status', AdvancePayment::STATUS_REVERSED)
        ->call('save')
        ->assertHasErrors(['reversal_reason']);
});

it('requires a cancellation reason when cancelled', function () {
    $vendor = VendorMaster::factory()->create();

    Livewire::test(Edit::class)
        ->set('vendor_id', $vendor->id)
        ->set('payment_mode_id', $this->cash->id)
        ->set('amount', 100)
        ->set('payment_status', AdvancePayment::STATUS_CANCELLED)
        ->call('save')
        ->assertHasErrors(['cancellation_reason']);
});

it('uppercases the reference number on save', function () {
    $vendor = VendorMaster::factory()->create();

    Livewire::test(Edit::class)
        ->set('vendor_id', $vendor->id)
        ->set('payment_mode_id', $this->cash->id)
        ->set('amount', 100)
        ->set('reference_no', 'utr998877')
        ->call('save')
        ->assertHasNoErrors();

    expect(AdvancePayment::first()->reference_no)->toBe('UTR998877');
});

it('edits an existing payment and keeps its number', function () {
    $payment = AdvancePayment::factory()->create(['amount' => 1000]);
    $original = $payment->payment_no;

    Livewire::test(Edit::class, ['advancePayment' => $payment])
        ->set('amount', 7500)
        ->set('payment_status', AdvancePayment::STATUS_REVERSED)
        ->set('reversal_reason', 'wrong_amount')
        ->call('save')
        ->assertHasNoErrors();

    $payment->refresh();
    expect($payment->payment_no)->toBe($original)
        ->and((float) $payment->amount)->toBe(7500.0)
        ->and($payment->payment_status)->toBe(AdvancePayment::STATUS_REVERSED);
});

it('reports KPI counts on the index', function () {
    AdvancePayment::factory()->count(2)->create();
    AdvancePayment::factory()->cancelled()->create();
    AdvancePayment::factory()->reversed()->create();

    Livewire::test(Index::class)
        ->assertViewHas('kpis', fn ($kpis) => $kpis['posted'] === 2 && $kpis['cancelled'] === 1 && $kpis['reversed'] === 1);
});

it('deletes a payment', function () {
    $payment = AdvancePayment::factory()->create();

    Livewire::test(Index::class)->call('delete', $payment->id);

    expect(AdvancePayment::find($payment->id))->toBeNull();
});

it('downloads the payment report as a CSV stream', function () {
    AdvancePayment::factory()->count(2)->create();

    Livewire::test(Index::class)->call('download')->assertFileDownloaded();
});
