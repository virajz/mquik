<?php

use App\Modules\EmployeeMaster\Models\EmployeeMaster;
use App\Modules\InternalWorkOrder\Livewire\Edit;
use App\Modules\InternalWorkOrder\Livewire\Index;
use App\Modules\InternalWorkOrder\Models\InternalWorkOrder;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(adminUser());
});

it('renders the index page', function () {
    InternalWorkOrder::factory()->count(3)->create();

    $this->get(route('internal-work-order.index'))->assertOk()->assertSeeLivewire(Index::class);
});

it('renders the create page', function () {
    $this->get(route('internal-work-order.create'))->assertOk()->assertSeeLivewire(Edit::class);
});

it('creates an IWO with an IWO- number and stamps complaint_at', function () {
    Livewire::test(Edit::class)
        ->set('iwo_type', 'raise_complaint')
        ->set('iwo_category', 'cctv')
        ->set('priority', 'high')
        ->set('department', 'it')
        ->set('title', 'cctv camera 3 offline')
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect(route('internal-work-order.index'));

    $iwo = InternalWorkOrder::first();
    expect($iwo->iwo_no)->toBe('IWO-'.str_pad((string) $iwo->id, 5, '0', STR_PAD_LEFT))
        ->and($iwo->title)->toBe('CCTV CAMERA 3 OFFLINE')
        ->and($iwo->complaint_at)->not->toBeNull();
});

it('stamps assigned_at, work_started_at and resolved_at as it advances', function () {
    $handler = EmployeeMaster::factory()->create();

    Livewire::test(Edit::class)
        ->set('title', 'printer jam')
        ->set('assigned_to_id', $handler->id)
        ->set('status', InternalWorkOrder::STATUS_IN_PROGRESS)
        ->call('save')
        ->assertHasNoErrors();

    $iwo = InternalWorkOrder::first();
    expect($iwo->assigned_at)->not->toBeNull()
        ->and($iwo->work_started_at)->not->toBeNull();

    Livewire::test(Edit::class, ['internalWorkOrder' => $iwo])
        ->set('status', InternalWorkOrder::STATUS_RESOLVED)
        ->set('corrective_action', 'repair')
        ->call('save')
        ->assertHasNoErrors();

    expect($iwo->fresh()->resolved_at)->not->toBeNull();
});

it('requires a corrective action when resolved', function () {
    Livewire::test(Edit::class)
        ->set('title', 'ac not cooling')
        ->set('status', InternalWorkOrder::STATUS_RESOLVED)
        ->call('save')
        ->assertHasErrors(['corrective_action']);
});

it('requires a title', function () {
    Livewire::test(Edit::class)
        ->set('title', '')
        ->call('save')
        ->assertHasErrors(['title']);
});

it('reports total / open / assigned / overdue / resolved-today / avg-TAT KPIs', function () {
    InternalWorkOrder::factory()->create();                       // open, requested
    InternalWorkOrder::factory()->assigned()->create();           // open + assigned + in_progress
    InternalWorkOrder::factory()->overdue()->create();            // open + overdue
    InternalWorkOrder::factory()->resolvedToday()->create();      // resolved today, 4h TAT

    Livewire::test(Index::class)
        ->assertViewHas('kpis', fn ($kpis) => $kpis['total'] === 4
            && $kpis['open'] === 3
            && $kpis['assigned'] === 1
            && $kpis['overdue'] === 1
            && $kpis['resolved_today'] === 1
            && $kpis['avg_tat_hours'] === 4);
});

it('deletes an IWO', function () {
    $iwo = InternalWorkOrder::factory()->create();

    Livewire::test(Index::class)->call('delete', $iwo->id);

    expect(InternalWorkOrder::find($iwo->id))->toBeNull();
});

it('downloads the IWO register report as a CSV stream', function () {
    InternalWorkOrder::factory()->count(2)->create();

    Livewire::test(Index::class)->call('download')->assertFileDownloaded();
});
