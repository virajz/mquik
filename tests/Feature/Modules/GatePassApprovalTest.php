<?php

use App\Modules\GatePassApproval\Livewire\Edit;
use App\Modules\GatePassApproval\Livewire\Index;
use App\Modules\GatePassApproval\Models\GatePassApproval;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(adminUser());
});

it('renders the index page', function () {
    GatePassApproval::factory()->count(3)->create();

    $this->get(route('gate-pass-approval.index'))->assertOk()->assertSeeLivewire(Index::class);
});

it('renders the create page', function () {
    $this->get(route('gate-pass-approval.create'))->assertOk()->assertSeeLivewire(Edit::class);
});

it('raises an approval and auto-calculates outstanding, exposure and authority', function () {
    Livewire::test(Edit::class)
        ->set('priority', 'high')
        ->set('credit_type', 'partial_pending')
        ->set('invoice_amount', 40000)
        ->set('receipt_amount', 10000)
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect(route('gate-pass-approval.index'));

    $g = GatePassApproval::first();
    expect($g->approval_no)->toBe('GPA-'.str_pad((string) $g->id, 5, '0', STR_PAD_LEFT))
        ->and($g->requested_at)->not->toBeNull()
        ->and((float) $g->outstanding_amount)->toBe(30000.0)
        ->and((float) $g->credit_exposure)->toBe(30000.0)
        ->and($g->approval_authority)->toBe(GatePassApproval::AUTHORITY_ADMIN); // 30k > 25k
});

it('routes to advisor authority when the exposure is within the matrix threshold', function () {
    Livewire::test(Edit::class)
        ->set('invoice_amount', 20000)
        ->set('receipt_amount', 0)
        ->call('save')
        ->assertHasNoErrors();

    expect(GatePassApproval::first()->approval_authority)->toBe(GatePassApproval::AUTHORITY_ADVISOR); // 20k <= 25k
});

it('recomputes outstanding live as amounts change', function () {
    $component = Livewire::test(Edit::class)
        ->set('invoice_amount', 15000)
        ->set('receipt_amount', 5000);

    expect((float) $component->get('outstanding_amount'))->toBe(10000.0)
        ->and($component->get('approval_authority'))->toBe(GatePassApproval::AUTHORITY_ADVISOR);
});

it('requires a priority', function () {
    Livewire::test(Edit::class)
        ->set('priority', '')
        ->call('save')
        ->assertHasErrors(['priority']);
});

it('requires a cancellation reason when cancelled', function () {
    Livewire::test(Edit::class)
        ->set('status', GatePassApproval::STATUS_CANCELLED)
        ->call('save')
        ->assertHasErrors(['cancellation_reason']);
});

it('stamps approved_at when a pending credit is approved', function () {
    $approval = GatePassApproval::factory()->create();

    Livewire::test(Edit::class, ['gatePassApproval' => $approval])
        ->set('status', GatePassApproval::STATUS_FULL_APPROVED)
        ->call('save')
        ->assertHasNoErrors();

    expect($approval->fresh()->approved_at)->not->toBeNull();
});

it('reports pending / delivered-outstanding / outstanding-value KPIs', function () {
    GatePassApproval::factory()->count(2)->create(); // requested -> pending
    GatePassApproval::factory()->fullApproved()->create(['outstanding_amount' => 8000]);
    GatePassApproval::factory()->partialApproved()->create(['outstanding_amount' => 2000]);

    Livewire::test(Index::class)
        ->assertViewHas('kpis', fn ($kpis) => $kpis['pending'] === 2
            && $kpis['delivered_outstanding'] === 2
            && (float) $kpis['outstanding_value'] === 10000.0);
});

it('deletes an approval', function () {
    $g = GatePassApproval::factory()->create();

    Livewire::test(Index::class)->call('delete', $g->id);

    expect(GatePassApproval::find($g->id))->toBeNull();
});

it('downloads the gate pass report as a CSV stream', function () {
    GatePassApproval::factory()->count(2)->create();

    Livewire::test(Index::class)->call('download')->assertFileDownloaded();
});
