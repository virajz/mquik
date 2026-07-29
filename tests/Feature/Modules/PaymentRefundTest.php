<?php

use App\Modules\AdvancePayment\Models\AdvancePayment;
use App\Modules\PaymentRefund\Livewire\Edit;
use App\Modules\PaymentRefund\Livewire\Index;
use App\Modules\PaymentRefund\Models\PaymentRefund;
use App\Modules\VendorMaster\Models\VendorMaster;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(adminUser());
});

it('renders the index page', function () {
    PaymentRefund::factory()->count(3)->create();

    $this->get(route('payment-refund.index'))->assertOk()->assertSeeLivewire(Index::class);
});

it('renders the create page', function () {
    $this->get(route('payment-refund.create'))->assertOk()->assertSeeLivewire(Edit::class);
});

it('records a refund and stamps requested_at', function () {
    $vendor = VendorMaster::factory()->create();

    Livewire::test(Edit::class)
        ->set('vendor_id', $vendor->id)
        ->set('refund_against', 'advance_payment')
        ->set('refund_type', 'excess_payment')
        ->set('priority', 'high')
        ->set('refund_mode', 'neft')
        ->set('reference_no', 'utr-5566')
        ->set('amount', 12000)
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect(route('payment-refund.index'));

    $r = PaymentRefund::first();
    expect($r->refund_no)->toBe('PRF-'.str_pad((string) $r->id, 5, '0', STR_PAD_LEFT))
        ->and($r->requested_at)->not->toBeNull()
        ->and($r->reference_no)->toBe('UTR-5566')
        ->and((float) $r->amount)->toBe(12000.0);
});

it('pulls the amount from the linked advance payment', function () {
    $payment = AdvancePayment::factory()->create(['amount' => 7700]);

    $component = Livewire::test(Edit::class)
        ->set('advance_payment_id', $payment->id);

    expect((float) $component->get('amount'))->toBe(7700.0);
});

it('requires a vendor', function () {
    Livewire::test(Edit::class)
        ->set('vendor_id', null)
        ->call('save')
        ->assertHasErrors(['vendor_id']);
});

it('requires a cheque bounce reason when the cheque bounced', function () {
    $vendor = VendorMaster::factory()->create();

    Livewire::test(Edit::class)
        ->set('vendor_id', $vendor->id)
        ->set('refund_mode', 'cheque')
        ->set('cheque_status', 'bounce')
        ->call('save')
        ->assertHasErrors(['cheque_bounce_reason_id']);
});

it('requires reasons for hold / rejected / cancelled statuses', function () {
    $vendor = VendorMaster::factory()->create();

    Livewire::test(Edit::class)
        ->set('vendor_id', $vendor->id)
        ->set('status', PaymentRefund::STATUS_ON_HOLD)
        ->call('save')
        ->assertHasErrors(['hold_reason']);

    Livewire::test(Edit::class)
        ->set('vendor_id', $vendor->id)
        ->set('status', PaymentRefund::STATUS_REJECTED)
        ->call('save')
        ->assertHasErrors(['rejection_reason']);

    Livewire::test(Edit::class)
        ->set('vendor_id', $vendor->id)
        ->set('status', PaymentRefund::STATUS_CANCELLED)
        ->call('save')
        ->assertHasErrors(['cancellation_reason']);
});

it('stamps refunded_at when created as refunded', function () {
    $vendor = VendorMaster::factory()->create();

    Livewire::test(Edit::class)
        ->set('vendor_id', $vendor->id)
        ->set('status', PaymentRefund::STATUS_REFUNDED)
        ->call('save')
        ->assertHasNoErrors();

    expect(PaymentRefund::first()->refunded_at)->not->toBeNull();
});

it('reports pending / received-today / month-value KPIs', function () {
    PaymentRefund::factory()->count(2)->create();                              // requested -> pending
    PaymentRefund::factory()->onHold()->create();                             // on hold -> pending
    PaymentRefund::factory()->refunded()->create(['amount' => 5000]);         // refunded today

    Livewire::test(Index::class)
        ->assertViewHas('kpis', fn ($kpis) => $kpis['pending'] === 3 && $kpis['received_today'] === 1 && (float) $kpis['value_month'] === 5000.0);
});

it('deletes a refund', function () {
    $r = PaymentRefund::factory()->create();

    Livewire::test(Index::class)->call('delete', $r->id);

    expect(PaymentRefund::find($r->id))->toBeNull();
});

it('downloads the payment report as a CSV stream', function () {
    PaymentRefund::factory()->count(2)->create();

    Livewire::test(Index::class)->call('download')->assertFileDownloaded();
});
