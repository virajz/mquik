<?php

use App\Modules\VehicleMovement\Livewire\Edit;
use App\Modules\VehicleMovement\Livewire\Index;
use App\Modules\VehicleMovement\Models\VehicleMovement;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(adminUser());
});

it('renders the index page', function () {
    VehicleMovement::factory()->count(3)->create();

    $this->get(route('vehicle-movement.index'))->assertOk()->assertSeeLivewire(Index::class);
});

it('renders the create page', function () {
    $this->get(route('vehicle-movement.create'))->assertOk()->assertSeeLivewire(Edit::class);
});

it('logs an inward movement and stamps the IO number', function () {
    Livewire::test(Edit::class)
        ->set('movement_type', 'inward')
        ->set('parking_slot', 'slot_2')
        ->set('gate', 'gate_1')
        ->set('number_plate', 'gj01ab1234')
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect(route('vehicle-movement.index'));

    $m = VehicleMovement::first();
    expect($m->movement_no)->toBe('IO-'.str_pad((string) $m->id, 5, '0', STR_PAD_LEFT))
        ->and($m->movement_type)->toBe('inward')
        ->and($m->number_plate)->toBe('GJ01AB1234');
});

it('requires an outward purpose for an outward movement', function () {
    Livewire::test(Edit::class)
        ->set('movement_type', 'outward')
        ->set('outward_type', null)
        ->call('save')
        ->assertHasErrors(['outward_type']);
});

it('clears the outward purpose on an inward movement', function () {
    Livewire::test(Edit::class)
        ->set('movement_type', 'inward')
        ->set('outward_type', 'trial_run')
        ->call('save')
        ->assertHasNoErrors();

    expect(VehicleMovement::first()->outward_type)->toBeNull();
});

it('rejects an exit time before the entry time', function () {
    Livewire::test(Edit::class)
        ->set('entry_at', '2026-07-24T10:00')
        ->set('exit_at', '2026-07-24T09:00')
        ->call('save')
        ->assertHasErrors(['exit_at']);
});

it('computes TAT from entry and exit', function () {
    $movement = VehicleMovement::factory()->create([
        'entry_at' => now()->setTime(10, 0),
        'exit_at' => now()->setTime(12, 30),
    ]);

    expect($movement->tatMinutes())->toBe(150)
        ->and($movement->tatLabel())->toBe('2h 30m');

    $open = VehicleMovement::factory()->create(['entry_at' => now(), 'exit_at' => null]);
    expect($open->tatMinutes())->toBeNull();
});

it('reports inward / trial / outward today KPIs', function () {
    VehicleMovement::factory()->count(2)->create();      // inward today
    VehicleMovement::factory()->outward()->create();     // outward (final delivery)
    VehicleMovement::factory()->trialRun()->create();    // outward (trial run)

    Livewire::test(Index::class)
        ->assertViewHas('kpis', fn ($kpis) => $kpis['inward_today'] === 2 && $kpis['trial_today'] === 1 && $kpis['outward_today'] === 2);
});

it('deletes a movement', function () {
    $m = VehicleMovement::factory()->create();

    Livewire::test(Index::class)->call('delete', $m->id);

    expect(VehicleMovement::find($m->id))->toBeNull();
});

it('downloads the inward/outward register as a CSV stream', function () {
    VehicleMovement::factory()->count(2)->create();

    Livewire::test(Index::class)->call('download')->assertFileDownloaded();
});
