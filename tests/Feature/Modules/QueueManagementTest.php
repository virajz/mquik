<?php

use App\Models\User;
use App\Modules\QueueManagement\Livewire\Edit;
use App\Modules\QueueManagement\Livewire\Index;
use App\Modules\QueueManagement\Models\ServiceQueue;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(adminUser());
});

it('renders the index page', function () {
    ServiceQueue::factory()->count(3)->create();

    $this->get(route('queue-management.index'))
        ->assertOk()
        ->assertSeeLivewire(Index::class);
});

it('renders the create page', function () {
    $this->get(route('queue-management.create'))
        ->assertOk()
        ->assertSeeLivewire(Edit::class);
});

it('adds a vehicle to the queue and stamps the number', function () {
    Livewire::test(Edit::class)
        ->set('queue_type', 'car_wash')
        ->set('job_description', 'full wash')
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect(route('queue-management.index'));

    $q = ServiceQueue::first();
    expect($q->queue_no)->toBe('Q-'.str_pad((string) $q->id, 5, '0', STR_PAD_LEFT))
        ->and($q->queue_type)->toBe('car_wash')
        ->and($q->job_description)->toBe('FULL WASH')
        ->and($q->status)->toBe(ServiceQueue::STATUS_WAITING);
});

it('requires a high-priority reason when high priority is on', function () {
    Livewire::test(Edit::class)
        ->set('is_high_priority', true)
        ->call('save')
        ->assertHasErrors(['high_priority_reason']);
});

it('clears high-priority fields when high priority is off', function () {
    $q = ServiceQueue::factory()->highPriority()->create();

    Livewire::test(Edit::class, ['serviceQueue' => $q])
        ->set('is_high_priority', false)
        ->call('save')
        ->assertHasNoErrors();

    expect($q->fresh()->high_priority_reason)->toBeNull();
});

it('requires a pause reason when status is on hold', function () {
    Livewire::test(Edit::class)
        ->set('status', ServiceQueue::STATUS_ON_HOLD)
        ->call('save')
        ->assertHasErrors(['pause_reason']);
});

it('computes waiting, service and TAT minutes', function () {
    $q = ServiceQueue::factory()->create([
        'kept_at' => now()->subMinutes(60),
        'work_started_at' => now()->subMinutes(45),
        'work_ended_at' => now()->subMinutes(15),
    ]);

    expect($q->waitingMinutes())->toBe(15)
        ->and($q->serviceMinutes())->toBe(30)
        ->and($q->tatMinutes())->toBe(45);
});

it('flags completion within the expected time as on-time', function () {
    $onTime = ServiceQueue::factory()->create([
        'work_ended_at' => now()->subMinutes(10),
        'expected_completion_at' => now(),
    ]);
    $late = ServiceQueue::factory()->create([
        'work_ended_at' => now(),
        'expected_completion_at' => now()->subMinutes(10),
    ]);

    expect($onTime->isOnTime())->toBeTrue()
        ->and($late->isOnTime())->toBeFalse();
});

it('computes the dashboard KPIs including on-time percent', function () {
    // 60m kept, work 45m→15m ago: wait 15, wash 30; ended 15m ago before expected now → on-time.
    ServiceQueue::factory()->completed()->create();
    ServiceQueue::factory()->create(); // waiting -> pending

    Livewire::test(Index::class)
        ->assertViewHas('kpis', fn ($kpis) => $kpis['total'] === 2
            && $kpis['pending'] === 1
            && $kpis['completed'] === 1
            && $kpis['on_time_pct'] === 100);
});

it('filters by screen view and type', function () {
    $ready = ServiceQueue::factory()->completed()->create(['queue_type' => 'detailing']); // screen_view ready
    $upcoming = ServiceQueue::factory()->create(['queue_type' => 'car_wash']);

    Livewire::test(Index::class)
        ->set('viewFilter', 'ready')
        ->assertSee($ready->queue_no)
        ->assertDontSee($upcoming->queue_no);

    Livewire::test(Index::class)
        ->set('typeFilter', 'detailing')
        ->assertSee($ready->queue_no)
        ->assertDontSee($upcoming->queue_no);
});

it('exports the queue as CSV', function () {
    ServiceQueue::factory()->completed()->create();

    Livewire::test(Index::class)
        ->call('download')
        ->assertFileDownloaded();
});

it('deletes a queue entry from the index', function () {
    $q = ServiceQueue::factory()->create();

    Livewire::test(Index::class)->call('delete', $q->id);

    expect(ServiceQueue::find($q->id))->toBeNull();
});

it('blocks the create page without permission', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('queue_management.view');
    $this->actingAs($user);

    $this->get(route('queue-management.create'))->assertForbidden();
});

it('requires authentication', function () {
    auth()->logout();
    $this->get(route('queue-management.index'))->assertRedirect(route('login'));
});
