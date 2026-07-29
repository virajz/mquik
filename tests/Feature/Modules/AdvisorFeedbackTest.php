<?php

use App\Modules\AdvisorFeedback\Livewire\Edit;
use App\Modules\AdvisorFeedback\Livewire\Index;
use App\Modules\AdvisorFeedback\Models\AdvisorFeedback;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(adminUser());
});

it('renders the index page', function () {
    AdvisorFeedback::factory()->count(3)->create();

    $this->get(route('advisor-feedback.index'))->assertOk()->assertSeeLivewire(Index::class);
});

it('renders the create page', function () {
    $this->get(route('advisor-feedback.create'))->assertOk()->assertSeeLivewire(Edit::class);
});

it('records advisor feedback with ratings', function () {
    Livewire::test(Edit::class)
        ->set('cooperative_rating', 4)
        ->set('timely_approvals_rating', 5)
        ->set('payment_committed_rating', 3)
        ->set('professional_rating', 4)
        ->set('prefer_again_rating', 4)
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect(route('advisor-feedback.index'));

    $f = AdvisorFeedback::first();
    expect($f->feedback_no)->toBe('AF-'.str_pad((string) $f->id, 5, '0', STR_PAD_LEFT))
        ->and($f->cooperative_rating)->toBe(4)
        ->and($f->averageRating())->toBe(4.0);
});

it('stamps submitted_at when the status is submitted', function () {
    Livewire::test(Edit::class)
        ->set('status', AdvisorFeedback::STATUS_SUBMITTED)
        ->set('cooperative_rating', 5)
        ->call('save')
        ->assertHasNoErrors();

    expect(AdvisorFeedback::first()->submitted_at)->not->toBeNull();
});

it('rejects a rating outside 1-5', function () {
    Livewire::test(Edit::class)
        ->set('cooperative_rating', 8)
        ->call('save')
        ->assertHasErrors(['cooperative_rating']);
});

it('requires a status', function () {
    Livewire::test(Edit::class)
        ->set('status', '')
        ->call('save')
        ->assertHasErrors(['status']);
});

it('averages only the given answers', function () {
    $feedback = AdvisorFeedback::factory()->create([
        'cooperative_rating' => 4,
        'professional_rating' => 2,
    ]);

    expect($feedback->averageRating())->toBe(3.0);
    expect(AdvisorFeedback::factory()->create()->averageRating())->toBeNull();
});

it('reports pending / submitted / avg-rating KPIs', function () {
    AdvisorFeedback::factory()->count(2)->create();     // pending
    AdvisorFeedback::factory()->submitted()->create();  // avg = (4+5+5+4+5)/5 = 4.6

    Livewire::test(Index::class)
        ->assertViewHas('kpis', fn ($kpis) => $kpis['pending'] === 2 && $kpis['submitted'] === 1 && (float) $kpis['avg_rating'] === 4.6);
});

it('deletes advisor feedback', function () {
    $f = AdvisorFeedback::factory()->create();

    Livewire::test(Index::class)->call('delete', $f->id);

    expect(AdvisorFeedback::find($f->id))->toBeNull();
});

it('downloads the advisor feedback export as a CSV stream', function () {
    AdvisorFeedback::factory()->count(2)->create();

    Livewire::test(Index::class)->call('download')->assertFileDownloaded();
});
