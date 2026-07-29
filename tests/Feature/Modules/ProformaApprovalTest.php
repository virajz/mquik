<?php

use App\Modules\ProformaApproval\Livewire\Edit;
use App\Modules\ProformaApproval\Livewire\Index;
use App\Modules\ProformaApproval\Models\ProformaApproval;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(adminUser());
});

it('renders the index page', function () {
    ProformaApproval::factory()->count(3)->create();

    $this->get(route('proforma-approval.index'))->assertOk()->assertSeeLivewire(Index::class);
});

it('renders the create page', function () {
    $this->get(route('proforma-approval.create'))->assertOk()->assertSeeLivewire(Edit::class);
});

it('raises an approval with a checkpoint and stamps requested_at', function () {
    Livewire::test(Edit::class)
        ->set('approval_stage', 'stage_1')
        ->set('approval_authority', 'billing_executive')
        ->set('priority', 'high')
        ->set('amount', 30000)
        ->set('checkpoints.0.role', 'billing_executive')
        ->set('checkpoints.0.checkpoint', 'ipo_qty_mismatch')
        ->set('checkpoints.0.status', 'flagged')
        ->set('checkpoints.0.note', 'qty short by 2')
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect(route('proforma-approval.index'));

    $p = ProformaApproval::with('checkpoints')->first();
    expect($p->approval_no)->toBe('PFA-'.str_pad((string) $p->id, 5, '0', STR_PAD_LEFT))
        ->and($p->requested_at)->not->toBeNull()
        ->and($p->checkpoints)->toHaveCount(1)
        ->and($p->checkpoints->first()->checkpoint)->toBe('ipo_qty_mismatch')
        ->and($p->checkpoints->first()->status)->toBe('flagged')
        ->and($p->checkpoints->first()->note)->toBe('QTY SHORT BY 2');
});

it('rejects a checkpoint whose key does not belong to any role list', function () {
    Livewire::test(Edit::class)
        ->set('checkpoints.0.role', 'billing_executive')
        ->set('checkpoints.0.checkpoint', 'not_a_real_checkpoint')
        ->call('save')
        ->assertHasErrors(['checkpoints.0.checkpoint']);
});

it('stamps prepared_at once the status leaves under preparation', function () {
    Livewire::test(Edit::class)
        ->set('status', ProformaApproval::STATUS_STORE_PENDING)
        ->call('save')
        ->assertHasNoErrors();

    expect(ProformaApproval::first()->prepared_at)->not->toBeNull();
});

it('stamps converted_at when the status becomes converted', function () {
    $approval = ProformaApproval::factory()->adminPending()->create();

    Livewire::test(Edit::class, ['proformaApproval' => $approval])
        ->set('status', ProformaApproval::STATUS_CONVERTED)
        ->call('save')
        ->assertHasNoErrors();

    expect($approval->fresh()->converted_at)->not->toBeNull();
});

it('requires a rejection reason when returned for correction', function () {
    Livewire::test(Edit::class)
        ->set('status', ProformaApproval::STATUS_RETURN_FOR_CORRECTION)
        ->call('save')
        ->assertHasErrors(['rejection_reason']);
});

it('requires a priority', function () {
    Livewire::test(Edit::class)
        ->set('priority', '')
        ->call('save')
        ->assertHasErrors(['priority']);
});

it('reports pending store / advisor / admin approval KPIs', function () {
    ProformaApproval::factory()->storePending()->count(2)->create();
    ProformaApproval::factory()->advisorPending()->create();
    ProformaApproval::factory()->adminPending()->count(3)->create();

    Livewire::test(Index::class)
        ->assertViewHas('kpis', fn ($kpis) => $kpis['store'] === 2 && $kpis['advisor'] === 1 && $kpis['admin'] === 3);
});

it('deletes an approval', function () {
    $p = ProformaApproval::factory()->create();

    Livewire::test(Index::class)->call('delete', $p->id);

    expect(ProformaApproval::find($p->id))->toBeNull();
});

it('downloads the proforma report as a CSV stream', function () {
    ProformaApproval::factory()->count(2)->create();

    Livewire::test(Index::class)->call('download')->assertFileDownloaded();
});
