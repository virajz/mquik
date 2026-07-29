<?php

use App\Modules\DeliveryOrder\Livewire\Edit;
use App\Modules\DeliveryOrder\Livewire\Index;
use App\Modules\DeliveryOrder\Models\DeliveryOrder;
use App\Modules\ProformaApproval\Models\ProformaApproval;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(adminUser());
});

it('renders the index page', function () {
    DeliveryOrder::factory()->count(3)->create();

    $this->get(route('delivery-order.index'))->assertOk()->assertSeeLivewire(Index::class);
});

it('renders the create page', function () {
    $this->get(route('delivery-order.create'))->assertOk()->assertSeeLivewire(Edit::class);
});

it('records a DO and stamps requested_at', function () {
    Livewire::test(Edit::class)
        ->set('claim_number', 'clm-9001')
        ->set('policy_number', 'pol-55')
        ->set('proforma_amount', 50000)
        ->set('do_amount', 50000)
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect(route('delivery-order.index'));

    $d = DeliveryOrder::first();
    expect($d->do_no)->toBe('DO-'.str_pad((string) $d->id, 5, '0', STR_PAD_LEFT))
        ->and($d->requested_at)->not->toBeNull()
        ->and($d->claim_number)->toBe('CLM-9001')
        ->and($d->hasMismatch())->toBeFalse();
});

it('pulls the proforma amount from the linked proforma approval', function () {
    $proforma = ProformaApproval::factory()->create(['amount' => 33000]);

    $component = Livewire::test(Edit::class)
        ->set('proforma_approval_id', $proforma->id);

    expect((float) $component->get('proforma_amount'))->toBe(33000.0);
});

it('flags a mismatch and requires a mismatch reason', function () {
    Livewire::test(Edit::class)
        ->set('proforma_amount', 50000)
        ->set('do_amount', 42000)
        ->call('save')
        ->assertHasErrors(['mismatch_reason']);
});

it('accepts a mismatched DO once a reason is given', function () {
    Livewire::test(Edit::class)
        ->set('proforma_amount', 50000)
        ->set('do_amount', 42000)
        ->set('mismatch_reason', 'depreciation')
        ->set('status', DeliveryOrder::STATUS_RECEIVED)
        ->call('save')
        ->assertHasNoErrors();

    $d = DeliveryOrder::first();
    expect($d->hasMismatch())->toBeTrue()
        ->and($d->mismatchDelta())->toBe(-8000.0)
        ->and($d->do_received_at)->not->toBeNull();
});

it('requires custom reminder days when frequency is custom', function () {
    Livewire::test(Edit::class)
        ->set('reminder_frequency', 'custom')
        ->call('save')
        ->assertHasErrors(['reminder_custom_days']);
});

it('reports DO pending / received / mismatch KPIs', function () {
    DeliveryOrder::factory()->count(2)->create();       // requested -> pending
    DeliveryOrder::factory()->received()->create();     // received, no mismatch
    DeliveryOrder::factory()->mismatch()->create();     // received, mismatch

    Livewire::test(Index::class)
        ->assertViewHas('kpis', fn ($kpis) => $kpis['pending'] === 2 && $kpis['received'] === 2 && $kpis['mismatch'] === 1);
});

it('deletes a DO', function () {
    $d = DeliveryOrder::factory()->create();

    Livewire::test(Index::class)->call('delete', $d->id);

    expect(DeliveryOrder::find($d->id))->toBeNull();
});

it('downloads the delivery order report as a CSV stream', function () {
    DeliveryOrder::factory()->count(2)->create();

    Livewire::test(Index::class)->call('download')->assertFileDownloaded();
});
