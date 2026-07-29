<?php

use App\Modules\AmcServiceFollowUp\Livewire\Edit;
use App\Modules\AmcServiceFollowUp\Livewire\Index;
use App\Modules\AmcServiceFollowUp\Models\AmcServiceFollowUp;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(adminUser());
});

it('renders the index page', function () {
    AmcServiceFollowUp::factory()->count(3)->create();

    $this->get(route('amc-service-follow-up.index'))->assertOk()->assertSeeLivewire(Index::class);
});

it('renders the create page', function () {
    $this->get(route('amc-service-follow-up.create'))->assertOk()->assertSeeLivewire(Edit::class);
});

it('creates a follow-up and stamps due_generated_at', function () {
    Livewire::test(Edit::class)
        ->set('follow_up_type', 'amc_renewal')
        ->set('due_date', now()->addDays(10)->format('Y-m-d'))
        ->set('service_interval', '10000')
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect(route('amc-service-follow-up.index'));

    $f = AmcServiceFollowUp::first();
    expect($f->follow_up_no)->toBe('ASF-'.str_pad((string) $f->id, 5, '0', STR_PAD_LEFT))
        ->and($f->due_generated_at)->not->toBeNull()
        ->and($f->follow_up_type)->toBe('amc_renewal');
});

it('stamps response_at and job_card_open_at as it advances', function () {
    Livewire::test(Edit::class)
        ->set('customer_response', 'book_appointment')
        ->call('save')
        ->assertHasNoErrors();

    expect(AmcServiceFollowUp::first()->response_at)->not->toBeNull();

    $f = AmcServiceFollowUp::first();
    Livewire::test(Edit::class, ['amcServiceFollowUp' => $f])
        ->set('status', AmcServiceFollowUp::STATUS_CONVERTED)
        ->call('save')
        ->assertHasNoErrors();

    expect($f->fresh()->job_card_open_at)->not->toBeNull();
});

it('requires a lost reason when lost', function () {
    Livewire::test(Edit::class)
        ->set('status', AmcServiceFollowUp::STATUS_LOST)
        ->call('save')
        ->assertHasErrors(['lost_reason']);
});

it('requires an escalation reason when escalated', function () {
    Livewire::test(Edit::class)
        ->set('escalation', 'gm_owner')
        ->call('save')
        ->assertHasErrors(['escalation_reason']);
});

it('reports due / upcoming / overdue / appointment / lost / recovered KPIs', function () {
    AmcServiceFollowUp::factory()->create(['due_date' => now()]);              // due today
    AmcServiceFollowUp::factory()->create(['due_date' => now()->addDays(5)]);  // upcoming
    AmcServiceFollowUp::factory()->create(['due_date' => now()->subDays(3)]);  // overdue
    AmcServiceFollowUp::factory()->appointmentBooked()->create();
    AmcServiceFollowUp::factory()->lost()->create();
    AmcServiceFollowUp::factory()->recovered()->create();

    Livewire::test(Index::class)
        ->assertViewHas('kpis', fn ($kpis) => $kpis['due_today'] === 1
            && $kpis['upcoming'] >= 1
            && $kpis['overdue'] === 1
            && $kpis['appointments'] === 1
            && $kpis['lost'] === 1
            && $kpis['recovered'] === 1);
});

it('deletes a follow-up', function () {
    $f = AmcServiceFollowUp::factory()->create();

    Livewire::test(Index::class)->call('delete', $f->id);

    expect(AmcServiceFollowUp::find($f->id))->toBeNull();
});

it('downloads the AMC follow-up report as a CSV stream', function () {
    AmcServiceFollowUp::factory()->count(2)->create();

    Livewire::test(Index::class)->call('download')->assertFileDownloaded();
});
