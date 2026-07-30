<?php

use App\Modules\StockMismatchApproval\Livewire\Edit;
use App\Modules\StockMismatchApproval\Livewire\Index;
use App\Modules\StockMismatchApproval\Models\StockMismatchApproval;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(adminUser());
});

it('renders the index page', function () {
    StockMismatchApproval::factory()->count(3)->create();

    $this->get(route('stock-mismatch-approval.index'))->assertOk()->assertSeeLivewire(Index::class);
});

it('renders the create page', function () {
    $this->get(route('stock-mismatch-approval.create'))->assertOk()->assertSeeLivewire(Edit::class);
});

it('creates an approval and stamps approval_no and requested_at', function () {
    Livewire::test(Edit::class)
        ->set('variance_reason', 'wrong_issue')
        ->set('communication_mode', 'email')
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect(route('stock-mismatch-approval.index'));

    $a = StockMismatchApproval::first();
    expect($a->approval_no)->toBe('SMA-'.str_pad((string) $a->id, 5, '0', STR_PAD_LEFT))
        ->and($a->requested_at)->not->toBeNull()
        ->and($a->variance_reason)->toBe('wrong_issue');
});

it('requires a management response and stamps approved_at when approved', function () {
    $a = StockMismatchApproval::factory()->create();

    Livewire::test(Edit::class, ['stockMismatchApproval' => $a])
        ->set('approval_status', StockMismatchApproval::STATUS_APPROVED)
        ->call('save')
        ->assertHasErrors(['management_response']);

    Livewire::test(Edit::class, ['stockMismatchApproval' => $a])
        ->set('approval_status', StockMismatchApproval::STATUS_APPROVED)
        ->set('management_response', 'adjust')
        ->call('save')
        ->assertHasNoErrors();

    expect($a->fresh()->approved_at)->not->toBeNull();
});

it('stamps rejected_at when rejected', function () {
    $a = StockMismatchApproval::factory()->create();

    Livewire::test(Edit::class, ['stockMismatchApproval' => $a])
        ->set('approval_status', StockMismatchApproval::STATUS_REJECTED)
        ->call('save')
        ->assertHasNoErrors();

    expect($a->fresh()->rejected_at)->not->toBeNull();
});

it('reports requested / under_review / approved / rejected KPIs', function () {
    StockMismatchApproval::factory()->create();
    StockMismatchApproval::factory()->underReview()->create();
    StockMismatchApproval::factory()->approved()->create();
    StockMismatchApproval::factory()->rejected()->create();

    Livewire::test(Index::class)
        ->assertViewHas('kpis', fn ($kpis) => $kpis['requested'] === 1
            && $kpis['under_review'] === 1
            && $kpis['approved'] === 1
            && $kpis['rejected'] === 1);
});

it('deletes an approval', function () {
    $a = StockMismatchApproval::factory()->create();

    Livewire::test(Index::class)->call('delete', $a->id);

    expect(StockMismatchApproval::find($a->id))->toBeNull();
});

it('downloads the stock mismatch approval report as a CSV stream', function () {
    StockMismatchApproval::factory()->count(2)->create();

    Livewire::test(Index::class)->call('download')->assertFileDownloaded();
});
