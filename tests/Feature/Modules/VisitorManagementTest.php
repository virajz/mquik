<?php

use App\Modules\EmployeeMaster\Models\EmployeeMaster;
use App\Modules\VisitorManagement\Livewire\Edit;
use App\Modules\VisitorManagement\Livewire\Index;
use App\Modules\VisitorManagement\Models\VisitorVisit;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(adminUser());
});

it('renders the index page', function () {
    VisitorVisit::factory()->count(3)->create();

    $this->get(route('visitor-management.index'))->assertOk()->assertSeeLivewire(Index::class);
});

it('renders the create page', function () {
    $this->get(route('visitor-management.create'))->assertOk()->assertSeeLivewire(Edit::class);
});

it('creates a visit and stamps token_no and arrival_at', function () {
    Livewire::test(Edit::class)
        ->set('visit_purpose', 'periodic_maintenance')
        ->set('arrival_mode', 'walk_in')
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect(route('visitor-management.index'));

    $v = VisitorVisit::first();
    expect($v->token_no)->toBe('VMS-'.str_pad((string) $v->id, 5, '0', STR_PAD_LEFT))
        ->and($v->arrival_at)->not->toBeNull()
        ->and($v->visit_purpose)->toBe('periodic_maintenance');
});

it('stamps advisor_assigned_at when an advisor is set', function () {
    $advisor = EmployeeMaster::factory()->create();

    Livewire::test(Edit::class)
        ->set('assigned_to_id', $advisor->id)
        ->call('save')
        ->assertHasNoErrors();

    expect(VisitorVisit::first()->advisor_assigned_at)->not->toBeNull();
});

it('stamps consultation and job-card timestamps as it advances', function () {
    Livewire::test(Edit::class)->call('save')->assertHasNoErrors();

    $v = VisitorVisit::first();

    Livewire::test(Edit::class, ['visitorVisit' => $v])
        ->set('status', VisitorVisit::STATUS_CONSULTATION_STARTED)
        ->call('save')
        ->assertHasNoErrors();
    expect($v->fresh()->consultation_started_at)->not->toBeNull();

    Livewire::test(Edit::class, ['visitorVisit' => $v->fresh()])
        ->set('status', VisitorVisit::STATUS_CONSULTATION_COMPLETED)
        ->call('save')
        ->assertHasNoErrors();
    expect($v->fresh()->consultation_ended_at)->not->toBeNull();

    Livewire::test(Edit::class, ['visitorVisit' => $v->fresh()])
        ->set('status', VisitorVisit::STATUS_JOB_CARD_CREATED)
        ->call('save')
        ->assertHasNoErrors();
    expect($v->fresh()->job_card_created_at)->not->toBeNull()
        ->and($v->fresh()->exit_at)->not->toBeNull();
});

it('requires a no-show reason when cancelled', function () {
    Livewire::test(Edit::class)
        ->set('status', VisitorVisit::STATUS_CANCELLED)
        ->call('save')
        ->assertHasErrors(['no_show_reason']);
});

it('reports token / served / no-show / avg-wait KPIs', function () {
    VisitorVisit::factory()->today()->count(2)->create();     // 2 tokens today
    VisitorVisit::factory()->served()->create();              // served today + wait window
    VisitorVisit::factory()->noShow()->create();              // cancelled no-show

    Livewire::test(Index::class)
        ->assertViewHas('kpis', fn ($kpis) => $kpis['tokens_today'] >= 2
            && $kpis['served_today'] === 1
            && $kpis['no_shows'] === 1
            && $kpis['avg_wait_minutes'] >= 1);
});

it('deletes a visit', function () {
    $v = VisitorVisit::factory()->create();

    Livewire::test(Index::class)->call('delete', $v->id);

    expect(VisitorVisit::find($v->id))->toBeNull();
});

it('downloads the visitor management report as a CSV stream', function () {
    VisitorVisit::factory()->count(2)->create();

    Livewire::test(Index::class)->call('download')->assertFileDownloaded();
});
