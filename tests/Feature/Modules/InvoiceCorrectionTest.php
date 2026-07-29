<?php

use App\Modules\InvoiceCorrection\Livewire\Edit;
use App\Modules\InvoiceCorrection\Livewire\Index;
use App\Modules\InvoiceCorrection\Models\InvoiceCorrection;
use App\Modules\SpareMaster\Models\SpareMaster;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(adminUser());
});

it('renders the index page', function () {
    InvoiceCorrection::factory()->count(3)->create();

    $this->get(route('invoice-correction.index'))->assertOk()->assertSeeLivewire(Index::class);
});

it('renders the create page', function () {
    $this->get(route('invoice-correction.create'))->assertOk()->assertSeeLivewire(Edit::class);
});

it('raises a correction with an old/new line and stamps requested_at', function () {
    $spare = SpareMaster::factory()->create();

    Livewire::test(Edit::class)
        ->set('correction_request_type', 'spares_rate')
        ->set('correction_reason', 'wrong_parts_rate')
        ->set('priority', 'high')
        ->set('invoice_reference', 'mq/26-27/12345')
        ->set('items.0.item_type', 'spare')
        ->set('items.0.spare_id', $spare->id)
        ->set('items.0.description', 'rate fix')
        ->set('items.0.old_value', '500')
        ->set('items.0.new_value', '450')
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect(route('invoice-correction.index'));

    $c = InvoiceCorrection::with('items')->first();
    expect($c->correction_no)->toBe('INC-'.str_pad((string) $c->id, 5, '0', STR_PAD_LEFT))
        ->and($c->requested_at)->not->toBeNull()
        ->and($c->invoice_reference)->toBe('MQ/26-27/12345')
        ->and($c->items)->toHaveCount(1)
        ->and($c->items->first()->old_value)->toBe('500')
        ->and($c->items->first()->new_value)->toBe('450');
});

it('allows a header-only correction with no lines', function () {
    Livewire::test(Edit::class)
        ->set('correction_request_type', 'address')
        ->set('items.0.description', '')
        ->call('save')
        ->assertHasNoErrors();

    expect(InvoiceCorrection::first()->items)->toHaveCount(0);
});

it('requires a priority', function () {
    Livewire::test(Edit::class)
        ->set('priority', '')
        ->call('save')
        ->assertHasErrors(['priority']);
});

it('requires a rejection reason when rejected', function () {
    Livewire::test(Edit::class)
        ->set('status', InvoiceCorrection::STATUS_REJECTED)
        ->call('save')
        ->assertHasErrors(['rejection_reason']);
});

it('stamps approved_at and corrected_at as the status advances', function () {
    $correction = InvoiceCorrection::factory()->create();

    Livewire::test(Edit::class, ['invoiceCorrection' => $correction])
        ->set('status', InvoiceCorrection::STATUS_CORRECTED)
        ->call('save')
        ->assertHasNoErrors();

    $correction->refresh();
    expect($correction->approved_at)->not->toBeNull()
        ->and($correction->corrected_at)->not->toBeNull();
});

it('reports pending / approved / rejected / corrected KPIs', function () {
    InvoiceCorrection::factory()->count(2)->create();       // requested -> pending
    InvoiceCorrection::factory()->approved()->create();
    InvoiceCorrection::factory()->rejected()->create();
    InvoiceCorrection::factory()->corrected()->create();

    Livewire::test(Index::class)
        ->assertViewHas('kpis', fn ($kpis) => $kpis['pending'] === 2 && $kpis['approved'] === 1 && $kpis['rejected'] === 1 && $kpis['corrected'] === 1);
});

it('deletes a correction', function () {
    $c = InvoiceCorrection::factory()->create();

    Livewire::test(Index::class)->call('delete', $c->id);

    expect(InvoiceCorrection::find($c->id))->toBeNull();
});

it('downloads the correction report as a CSV stream', function () {
    InvoiceCorrection::factory()->count(2)->create();

    Livewire::test(Index::class)->call('download')->assertFileDownloaded();
});
