<?php

use App\Modules\ServiceIntervalMaster\Livewire\Index;
use App\Modules\ServiceIntervalMaster\Models\ServiceIntervalMaster;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(adminUser());
});

it('loads the service intervals page over http', function () {
    ServiceIntervalMaster::create(['name' => 'TIMING BELT', 'interval_months' => 60, 'interval_km' => 90000]);

    $this->get(route('service-interval-master.index'))
        ->assertOk()
        ->assertSee('Service Intervals')
        ->assertSee('TIMING BELT');
});

it('loads the workshop settings page over http', function () {
    $this->get(route('settings.workshop'))->assertOk()->assertSee('Service history');
});

it('opens the create modal from the New button', function () {
    Livewire::test(Index::class)
        ->call('openCreate')
        ->assertDispatched('service-interval-master:edit');
});

it('opens the edit modal from a row', function () {
    $row = ServiceIntervalMaster::factory()->create();

    Livewire::test(Index::class)
        ->call('openEdit', $row->id)
        ->assertDispatched('service-interval-master:edit', id: $row->id);
});

it('uses a flux confirm modal, never a browser alert', function () {
    $row = ServiceIntervalMaster::factory()->create(['name' => 'TIMING BELT']);

    $html = Livewire::test(Index::class)->html();

    expect($html)->not->toContain('wire:confirm')
        ->and($html)->toContain('service-interval-master-delete-'.$row->id)
        ->and($html)->toContain('Delete TIMING BELT?');
});

it('shows the settings page in the settings nav', function () {
    $this->get(route('settings.workshop'))
        ->assertOk()
        ->assertSee('Workshop')
        ->assertSee(route('service-interval-master.index'), escape: false);
});
