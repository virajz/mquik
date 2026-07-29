<?php

use App\Modules\ServiceDueFollowUp\Livewire\Edit;
use App\Modules\ServiceDueFollowUp\Livewire\Index;
use App\Modules\ServiceDueFollowUp\Models\ServiceDueFollowUp;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(adminUser());
});

it('renders the index page', function () {
    ServiceDueFollowUp::factory()->count(3)->create();

    $this->get(route('service-due-follow-up.index'))->assertOk()->assertSeeLivewire(Index::class);
});

it('renders the create page', function () {
    $this->get(route('service-due-follow-up.create'))->assertOk()->assertSeeLivewire(Edit::class);
});

it('creates a follow-up and stamps due_generated_at', function () {
    Livewire::test(Edit::class)
        ->set('service_interval_method', 'kilometer')
        ->set('service_interval', '10000')
        ->set('due_date', now()->addDays(10)->format('Y-m-d'))
        ->set('follow_up_attempt', 'first')
        ->set('follow_up_mode', 'whatsapp')
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect(route('service-due-follow-up.index'));

    $f = ServiceDueFollowUp::first();
    expect($f->follow_up_no)->toBe('SDF-'.str_pad((string) $f->id, 5, '0', STR_PAD_LEFT))
        ->and($f->due_generated_at)->not->toBeNull()
        ->and($f->service_interval)->toBe('10000');
});

it('stamps response_at when a customer response is recorded', function () {
    Livewire::test(Edit::class)
        ->set('customer_response', 'book_appointment')
        ->call('save')
        ->assertHasNoErrors();

    expect(ServiceDueFollowUp::first()->response_at)->not->toBeNull();
});

it('stamps job_card_open_at when converted', function () {
    Livewire::test(Edit::class)
        ->set('status', ServiceDueFollowUp::STATUS_CONVERTED)
        ->call('save')
        ->assertHasNoErrors();

    expect(ServiceDueFollowUp::first()->job_card_open_at)->not->toBeNull();
});

it('requires a lost reason when the opportunity is lost', function () {
    Livewire::test(Edit::class)
        ->set('status', ServiceDueFollowUp::STATUS_LOST)
        ->call('save')
        ->assertHasErrors(['lost_reason']);
});

it('requires an escalation reason when escalated', function () {
    Livewire::test(Edit::class)
        ->set('escalation', 'escalated_admin')
        ->call('save')
        ->assertHasErrors(['escalation_reason']);
});

it('reports due / upcoming / overdue / appointment / lost / recovered KPIs', function () {
    ServiceDueFollowUp::factory()->create(['due_date' => now()]);                 // due today (pending)
    ServiceDueFollowUp::factory()->create(['due_date' => now()->addDays(5)]);     // upcoming
    ServiceDueFollowUp::factory()->create(['due_date' => now()->subDays(3)]);     // overdue (pending)
    ServiceDueFollowUp::factory()->appointmentBooked()->create();
    ServiceDueFollowUp::factory()->lost()->create();
    ServiceDueFollowUp::factory()->recovered()->create();

    Livewire::test(Index::class)
        ->assertViewHas('kpis', fn ($kpis) => $kpis['due_today'] === 1
            && $kpis['upcoming'] >= 1
            && $kpis['overdue'] === 1
            && $kpis['appointments'] === 1
            && $kpis['lost'] === 1
            && $kpis['recovered'] === 1);
});

it('deletes a follow-up', function () {
    $f = ServiceDueFollowUp::factory()->create();

    Livewire::test(Index::class)->call('delete', $f->id);

    expect(ServiceDueFollowUp::find($f->id))->toBeNull();
});

it('downloads the follow-up report as a CSV stream', function () {
    ServiceDueFollowUp::factory()->count(2)->create();

    Livewire::test(Index::class)->call('download')->assertFileDownloaded();
});
