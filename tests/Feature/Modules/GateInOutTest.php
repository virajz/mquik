<?php

use App\Models\User;
use App\Modules\CustomerMaster\Models\CustomerMaster;
use App\Modules\CustomerVehicleMaster\Models\CustomerVehicleMaster;
use App\Modules\GateInOut\Livewire\Edit;
use App\Modules\GateInOut\Livewire\Index;
use App\Modules\GateInOut\Models\GateInOut;
use App\Modules\GateMaster\Models\GateMaster;
use App\Modules\ParkingSlotMaster\Models\ParkingSlotMaster;
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

it('filters by status, presence and source', function () {
    GateInOut::factory()->count(2)->create();     // still inside, pending
    GateInOut::factory()->out()->create();        // left, completed
    GateInOut::factory()->anpr()->create();

    Livewire::test(Index::class)
        ->set('statusFilter', GateInOut::STATUS_COMPLETED)
        ->assertViewHas('rows', fn ($rows) => $rows->count() === 1)
        ->set('statusFilter', 'all')
        ->set('presenceFilter', 'inside')
        ->assertViewHas('rows', fn ($rows) => $rows->count() === 3)
        ->set('presenceFilter', 'all')
        ->set('sourceFilter', 'anpr')
        ->assertViewHas('rows', fn ($rows) => $rows->count() === 1);
});

it('computes TAT from the entry and exit stamps', function () {
    $visit = GateInOut::factory()->create([
        'entered_at' => '2026-09-01 09:00:00',
        'exited_at' => '2026-09-01 12:30:00',
    ]);

    expect($visit->tatMinutes())->toBe(210)
        ->and($visit->tatForHumans())->toBe('3h 30m');
});

it('reports no TAT while the vehicle is still inside', function () {
    $visit = GateInOut::factory()->create(['exited_at' => null]);

    expect($visit->tatMinutes())->toBeNull()
        ->and($visit->tatForHumans())->toBe('—')
        ->and(GateInOut::stillInside()->count())->toBe(1);
});

it('rejects an exit that predates the entry', function () {
    Livewire::test(Edit::class)
        ->set('registration_no', 'GJ 05 AA 1234')
        ->set('entered_date', '2026-09-02')
        ->set('entered_time', '10:00')
        ->set('exited_date', '2026-09-01')
        ->set('exited_time', '10:00')
        ->call('save')
        ->assertHasErrors(['exited_date']);

    expect(GateInOut::count())->toBe(0);
});

it('records the outward leg with gate, type and driver type', function () {
    $gate = GateMaster::factory()->create(['name' => 'GATE NO. 2']);
    $slot = ParkingSlotMaster::factory()->create(['name' => 'SLOT NO. 1']);

    Livewire::test(Edit::class)
        ->set('registration_no', 'GJ 05 AA 1234')
        ->set('parking_slot_id', $slot->id)
        ->set('entered_date', '2026-09-01')
        ->set('entered_time', '09:00')
        ->set('exited_date', '2026-09-01')
        ->set('exited_time', '11:00')
        ->set('exit_gate_id', $gate->id)
        ->set('outward_type', 'trial_run')
        ->set('driver_type', 'workshop_staff')
        ->set('status', GateInOut::STATUS_COMPLETED)
        ->call('save')
        ->assertHasNoErrors();

    $visit = GateInOut::firstOrFail();
    expect($visit->exit_gate_id)->toBe($gate->id)
        ->and($visit->parking_slot_id)->toBe($slot->id)
        ->and($visit->outward_type)->toBe('trial_run')
        ->and($visit->driver_type)->toBe('workshop_staff')
        ->and($visit->tatMinutes())->toBe(120);
});

it('counts today\'s inward, outward and trial runs on the index', function () {
    GateInOut::factory()->create(['entered_at' => now(), 'exited_at' => null]);
    GateInOut::factory()->create(['entered_at' => now(), 'exited_at' => now(), 'outward_type' => 'trial_run']);
    GateInOut::factory()->create(['entered_at' => now()->subDays(3), 'exited_at' => now()->subDays(3)]);

    Livewire::test(Index::class)->assertViewHas('kpis', fn ($k) => $k['inward'] === 2
        && $k['outward'] === 1
        && $k['trialRun'] === 1
        && $k['inside'] === 1);
});

it('records a gate event with capital typing on notes', function () {
    Livewire::test(Edit::class)
        ->set('registration_no', 'gj 05 aa 1234')
        ->set('notes', 'late entry')
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect(route('gate-in-out.index'));

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

    Livewire::test(Edit::class)
        ->set('registration_no', 'gj 05  aa 1234')  // intentionally messy
        ->assertSet('customer_vehicle_id', $vehicle->id)
        ->assertSet('customer_id', $customer->id);
});

it('leaves customer_vehicle_id null when reg-no is unknown (walk-in)', function () {
    Livewire::test(Edit::class)
        ->set('registration_no', 'GJ 99 ZZ 9999')
        ->assertSet('customer_vehicle_id', null)
        ->assertSet('customer_id', null);
});

it('Edit::save blocks a user without create permission', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('gate_in_out.view');
    $this->actingAs($user);

    Livewire::test(Edit::class)
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
