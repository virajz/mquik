<?php

use App\Models\User;
use App\Modules\CustomerMaster\Models\CustomerMaster;
use App\Modules\CustomerVehicleMaster\Models\CustomerVehicleMaster;
use App\Modules\GateInOut\Livewire\Form;
use App\Modules\GateInOut\Livewire\Index;
use App\Modules\GateInOut\Models\GateInOut;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(adminUser());
});

it('renders the index page', function () {
    GateInOut::factory()->count(3)->create();

    $this->get(route('gate-in-out.index'))
        ->assertOk()
        ->assertSeeLivewire(Index::class);
});

it('auto-stamps GE-00001 style gate_event_no on create', function () {
    $row = GateInOut::factory()->create();

    expect($row->fresh()->gate_event_no)->toBe('GE-'.str_pad((string) $row->id, 5, '0', STR_PAD_LEFT));
});

it('normalises registration_no on save (whitespace + uppercase)', function () {
    $row = GateInOut::factory()->create(['registration_no' => '  gj 05  aa  1234  ']);

    expect($row->fresh()->registration_no)->toBe('GJ 05 AA 1234');
});

it('filters by direction and source', function () {
    GateInOut::factory()->count(2)->create();
    GateInOut::factory()->out()->create();
    GateInOut::factory()->anpr()->create();

    Livewire::test(Index::class)
        ->set('directionFilter', 'out')
        ->assertViewHas('rows', fn ($rows) => $rows->count() === 1)
        ->set('directionFilter', 'all')
        ->set('sourceFilter', 'anpr')
        ->assertViewHas('rows', fn ($rows) => $rows->count() === 1);
});

it('records a gate event with capital typing on notes', function () {
    Livewire::test(Form::class)
        ->dispatch('gate-in-out:edit', id: null)
        ->set('registration_no', 'gj 05 aa 1234')
        ->set('notes', 'late entry')
        ->call('save')
        ->assertHasNoErrors()
        ->assertDispatched('gate-in-out:saved');

    $row = GateInOut::first();
    expect($row->registration_no)->toBe('GJ 05 AA 1234')
        ->and($row->notes)->toBe('late entry') // notes is free-text; capital typing left to user
        ->and($row->source)->toBe(GateInOut::SOURCE_MANUAL)
        ->and($row->gate_event_no)->toStartWith('GE-')
        ->and($row->recorded_by_user_id)->toBe(auth()->id());
});

it('auto-resolves customer_vehicle_id when reg-no matches', function () {
    $customer = CustomerMaster::factory()->create();
    $vehicle = CustomerVehicleMaster::factory()->create([
        'customer_id' => $customer->id,
        'registration_no' => 'GJ 05 AA 1234',
    ]);

    Livewire::test(Form::class)
        ->dispatch('gate-in-out:edit', id: null)
        ->set('registration_no', 'gj 05  aa 1234')  // intentionally messy
        ->assertSet('customer_vehicle_id', $vehicle->id)
        ->assertSet('customer_id', $customer->id);
});

it('leaves customer_vehicle_id null when reg-no is unknown (walk-in)', function () {
    Livewire::test(Form::class)
        ->dispatch('gate-in-out:edit', id: null)
        ->set('registration_no', 'GJ 99 ZZ 9999')
        ->assertSet('customer_vehicle_id', null)
        ->assertSet('customer_id', null);
});

it('Form::save blocks a user without create permission', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('gate_in_out.view');
    $this->actingAs($user);

    Livewire::test(Form::class)
        ->dispatch('gate-in-out:edit', id: null)
        ->set('registration_no', 'GJ 05 AA 1234')
        ->call('save')
        ->assertStatus(403);

    expect(GateInOut::count())->toBe(0);
});

it('Index::delete blocks a user without delete permission', function () {
    $row = GateInOut::factory()->create();
    $user = User::factory()->create();
    $user->givePermissionTo('gate_in_out.view');
    $this->actingAs($user);

    Livewire::test(Index::class)
        ->call('delete', $row->id)
        ->assertStatus(403);
});

it('deletes a gate event from the index', function () {
    $row = GateInOut::factory()->create();

    Livewire::test(Index::class)->call('delete', $row->id);

    expect(GateInOut::find($row->id))->toBeNull();
});

it('requires authentication', function () {
    auth()->logout();

    $this->get(route('gate-in-out.index'))->assertRedirect(route('login'));
});
