<?php

use App\Modules\TimeSlotMaster\Livewire\Form;
use App\Modules\TimeSlotMaster\Livewire\Index;
use App\Modules\TimeSlotMaster\Models\TimeSlotMaster;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(adminUser());
});

it('renders the index page', function () {
    TimeSlotMaster::factory()->count(3)->create();

    $this->get(route('time-slot-master.index'))
        ->assertOk()
        ->assertSeeLivewire(Index::class);
});

it('creates a slot with window, capacity and buffer', function () {
    Livewire::test(Form::class)
        ->set('name', '09:00-10:00')
        ->set('code', 's1')
        ->set('slot_start_time', '09:00')
        ->set('slot_end_time', '10:00')
        ->set('max_vehicles_per_slot', 8)
        ->set('buffer_minutes', 15)
        ->call('save')
        ->assertHasNoErrors()
        ->assertDispatched('time-slot-master:saved');

    $slot = TimeSlotMaster::firstOrFail();
    expect($slot->name)->toBe('09:00-10:00')
        ->and($slot->code)->toBe('S1')
        ->and($slot->max_vehicles_per_slot)->toBe(8)
        ->and($slot->buffer_minutes)->toBe(15)
        ->and($slot->window())->toBe('09:00 – 10:00');
});

it('requires both times and rejects an end time before the start', function () {
    Livewire::test(Form::class)
        ->set('name', 'BAD SLOT')
        ->set('slot_start_time', '')
        ->set('slot_end_time', '')
        ->call('save')
        ->assertHasErrors(['slot_start_time', 'slot_end_time']);

    Livewire::test(Form::class)
        ->set('name', 'BACKWARDS SLOT')
        ->set('slot_start_time', '11:00')
        ->set('slot_end_time', '10:00')
        ->call('save')
        ->assertHasErrors(['slot_end_time']);
});

it('requires a capacity of at least one vehicle', function () {
    Livewire::test(Form::class)
        ->set('name', 'ZERO SLOT')
        ->set('slot_start_time', '09:00')
        ->set('slot_end_time', '10:00')
        ->set('max_vehicles_per_slot', 0)
        ->call('save')
        ->assertHasErrors(['max_vehicles_per_slot']);
});

it('lists slots in chronological order, not alphabetical', function () {
    TimeSlotMaster::factory()->window('16:00', '17:00')->create(['name' => 'EVENING SLOT']);
    TimeSlotMaster::factory()->window('09:00', '10:00')->create(['name' => 'MORNING SLOT']);

    $html = Livewire::test(Index::class)->html();

    expect(strpos($html, 'MORNING SLOT'))->toBeLessThan(strpos($html, 'EVENING SLOT'));
});

it('loads an existing slot back into the form as H:i', function () {
    $slot = TimeSlotMaster::factory()->window('14:00', '15:00', 3)->create(['name' => 'AFTERNOON']);

    Livewire::test(Form::class)
        ->dispatch('time-slot-master:edit', id: $slot->id)
        ->assertSet('slot_start_time', '14:00')
        ->assertSet('slot_end_time', '15:00')
        ->assertSet('max_vehicles_per_slot', 3);
});

it('filters by active status', function () {
    TimeSlotMaster::factory()->create(['name' => 'SLOT ENABLED']);
    TimeSlotMaster::factory()->inactive()->create(['name' => 'SLOT DISABLED']);

    Livewire::test(Index::class)->set('statusFilter', 'active')
        ->assertSee('SLOT ENABLED')
        ->assertDontSee('SLOT DISABLED');
});

it('validates name is required and unique', function () {
    TimeSlotMaster::factory()->create(['name' => 'EXISTING SLOT']);

    Livewire::test(Form::class)
        ->set('name', '')
        ->call('save')
        ->assertHasErrors(['name' => 'required']);

    Livewire::test(Form::class)
        ->set('name', 'EXISTING SLOT')
        ->set('slot_start_time', '09:00')
        ->set('slot_end_time', '10:00')
        ->call('save')
        ->assertHasErrors(['name' => 'unique']);
});

it('deletes a slot from the index', function () {
    $slot = TimeSlotMaster::factory()->create();

    Livewire::test(Index::class)->call('delete', $slot->id);

    expect(TimeSlotMaster::find($slot->id))->toBeNull();
});

it('requires authentication', function () {
    auth()->logout();

    $this->get(route('time-slot-master.index'))->assertRedirect(route('login'));
});
