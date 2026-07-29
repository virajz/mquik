<?php

use App\Modules\CustomerFeedback\Livewire\Edit;
use App\Modules\CustomerFeedback\Livewire\Index;
use App\Modules\CustomerFeedback\Models\CustomerFeedback;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(adminUser());
});

it('renders the index page', function () {
    CustomerFeedback::factory()->count(3)->create();

    $this->get(route('customer-feedback.index'))->assertOk()->assertSeeLivewire(Index::class);
});

it('renders the create page', function () {
    $this->get(route('customer-feedback.create'))->assertOk()->assertSeeLivewire(Edit::class);
});

it('records feedback and stamps requested_at', function () {
    Livewire::test(Edit::class)
        ->set('follow_up_schedule', '4_days')
        ->set('follow_up_mode', 'whatsapp')
        ->set('service_rating', 4)
        ->set('would_recommend', true)
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect(route('customer-feedback.index'));

    $f = CustomerFeedback::first();
    expect($f->feedback_no)->toBe('CF-'.str_pad((string) $f->id, 5, '0', STR_PAD_LEFT))
        ->and($f->requested_at)->not->toBeNull()
        ->and($f->service_rating)->toBe(4)
        ->and($f->would_recommend)->toBeTrue();
});

it('stamps submitted_at when the customer responds satisfied', function () {
    Livewire::test(Edit::class)
        ->set('status', CustomerFeedback::STATUS_SATISFIED)
        ->set('service_rating', 5)
        ->call('save')
        ->assertHasNoErrors();

    expect(CustomerFeedback::first()->submitted_at)->not->toBeNull();
});

it('requires custom follow-up days when the schedule is custom', function () {
    Livewire::test(Edit::class)
        ->set('follow_up_schedule', 'custom')
        ->call('save')
        ->assertHasErrors(['follow_up_custom_days']);
});

it('rejects a rating outside 1-5', function () {
    Livewire::test(Edit::class)
        ->set('service_rating', 7)
        ->call('save')
        ->assertHasErrors(['service_rating']);
});

it('averages the given rating answers', function () {
    $feedback = CustomerFeedback::factory()->create([
        'staff_experience_rating' => 4,
        'service_rating' => 5,
        'price_rating' => 3,
    ]);

    expect($feedback->averageRating())->toBe(4.0);

    $empty = CustomerFeedback::factory()->create();
    expect($empty->averageRating())->toBeNull();
});

it('reports avg-rating / response-rate / negative KPIs', function () {
    CustomerFeedback::factory()->count(2)->create();                 // sent, not submitted
    CustomerFeedback::factory()->satisfied()->create();              // service_rating 5, submitted
    CustomerFeedback::factory()->dissatisfied()->create();           // service_rating 2, submitted

    Livewire::test(Index::class)
        ->assertViewHas('kpis', fn ($kpis) => $kpis['total'] === 4
            && (float) $kpis['avg_rating'] === 3.5   // (5 + 2) / 2
            && (float) $kpis['response_rate'] === 50.0
            && $kpis['negative'] === 1);
});

it('deletes a feedback', function () {
    $f = CustomerFeedback::factory()->create();

    Livewire::test(Index::class)->call('delete', $f->id);

    expect(CustomerFeedback::find($f->id))->toBeNull();
});

it('downloads the feedback export as a CSV stream', function () {
    CustomerFeedback::factory()->count(2)->create();

    Livewire::test(Index::class)->call('download')->assertFileDownloaded();
});
