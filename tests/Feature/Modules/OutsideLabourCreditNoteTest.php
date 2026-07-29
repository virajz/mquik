<?php

use App\Modules\OutsideLabourCreditNote\Livewire\Edit;
use App\Modules\OutsideLabourCreditNote\Livewire\Index;
use App\Modules\OutsideLabourCreditNote\Models\OutsideLabourCreditNote;
use App\Modules\VendorMaster\Models\VendorMaster;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(adminUser());
});

it('renders the index page', function () {
    OutsideLabourCreditNote::factory()->count(3)->create();

    $this->get(route('outside-labour-credit-note.index'))->assertOk()->assertSeeLivewire(Index::class);
});

it('renders the create page', function () {
    $this->get(route('outside-labour-credit-note.create'))->assertOk()->assertSeeLivewire(Edit::class);
});

it('posts a credit note with a line and stamps an OLCN number', function () {
    $vendor = VendorMaster::factory()->create();

    Livewire::test(Edit::class)
        ->set('note_type', 'credit_note')
        ->set('vendor_id', $vendor->id)
        ->set('return_reason', 'workmanship_failure')
        ->set('amount', 3200)
        ->set('items.0.description', 'repaint bumper')
        ->set('items.0.quantity', 1)
        ->set('items.0.rate', 3200)
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect(route('outside-labour-credit-note.index'));

    $n = OutsideLabourCreditNote::with('items')->first();
    expect($n->note_no)->toBe('OLCN-'.str_pad((string) $n->id, 5, '0', STR_PAD_LEFT))
        ->and($n->items)->toHaveCount(1)
        ->and($n->items->first()->description)->toBe('REPAINT BUMPER');
});

it('stamps an OLDN number for a debit note', function () {
    $vendor = VendorMaster::factory()->create();

    Livewire::test(Edit::class)
        ->set('note_type', 'debit_note')
        ->set('vendor_id', $vendor->id)
        ->set('items.0.description', 'x')
        ->call('save')
        ->assertHasNoErrors();

    $n = OutsideLabourCreditNote::first();
    expect($n->note_no)->toBe('OLDN-'.str_pad((string) $n->id, 5, '0', STR_PAD_LEFT));
});

it('requires a vendor', function () {
    Livewire::test(Edit::class)
        ->set('vendor_id', null)
        ->set('items.0.description', 'x')
        ->call('save')
        ->assertHasErrors(['vendor_id']);
});

it('requires at least one line', function () {
    $vendor = VendorMaster::factory()->create();

    Livewire::test(Edit::class)
        ->set('vendor_id', $vendor->id)
        ->set('items.0.description', '')
        ->call('save')
        ->assertHasErrors(['items.0.description']);
});

it('reports KPI counts on the index', function () {
    OutsideLabourCreditNote::factory()->count(2)->create();      // credit posted
    OutsideLabourCreditNote::factory()->debitNote()->create();   // debit posted
    OutsideLabourCreditNote::factory()->cancelled()->create();   // cancelled

    Livewire::test(Index::class)
        ->assertViewHas('kpis', fn ($kpis) => $kpis['credit'] === 2 && $kpis['debit'] === 1 && $kpis['cancelled'] === 1);
});

it('deletes a note', function () {
    $n = OutsideLabourCreditNote::factory()->create();

    Livewire::test(Index::class)->call('delete', $n->id);

    expect(OutsideLabourCreditNote::find($n->id))->toBeNull();
});

it('downloads the register as a CSV stream', function () {
    OutsideLabourCreditNote::factory()->count(2)->create();

    Livewire::test(Index::class)->call('download')->assertFileDownloaded();
});
