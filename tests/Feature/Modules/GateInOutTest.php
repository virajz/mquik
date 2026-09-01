<?php

use App\Models\User;
use App\Modules\Appointment\Models\Appointment;
use App\Modules\CustomerMaster\Models\CustomerMaster;
use App\Modules\CustomerVehicleMaster\Models\CustomerVehicleMaster;
use App\Modules\DesignationMaster\Models\DesignationMaster;
use App\Modules\EmployeeMaster\Models\EmployeeMaster;
use App\Modules\GateInOut\Livewire\Edit;
use App\Modules\GateInOut\Livewire\Index;
use App\Modules\GateInOut\Models\GateInOut;
use App\Modules\GateMaster\Models\GateMaster;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
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

it('never takes an exit stamp from the form — only markDelivered writes it', function () {
    $gate = GateMaster::factory()->create();
    $row = GateInOut::factory()->create(['exited_at' => null, 'entry_gate_id' => $gate->id]);

    Livewire::test(Edit::class, ['gateInOut' => $row])
        ->set('exited_date', '2020-01-01')
        ->set('exited_time', '10:00')
        ->call('save')
        ->assertHasNoErrors();

    expect($row->fresh()->exited_at)->toBeNull()
        ->and($row->fresh()->status)->toBe(GateInOut::STATUS_PENDING);
});

it('stamps the delivery through markDelivered and completes the visit', function () {
    $gate = GateMaster::factory()->create(['name' => 'GATE NO. 2']);
    $row = GateInOut::factory()->create(['exited_at' => null]);

    $component = Livewire::test(Edit::class, ['gateInOut' => $row])
        ->set('exit_gate_id', null)
        ->set('driver_type', null)
        ->call('markDelivered')
        ->assertHasErrors(['exit_gate_id', 'driver_type', 'delivered_by_id', 'exit_by_id']);

    expect($row->fresh()->exited_at)->toBeNull();

    $component
        ->set('exit_gate_id', $gate->id)
        ->set('driver_type', 'workshop_staff')
        ->set('delivered_by_id', EmployeeMaster::factory()->create()->id)
        ->set('exit_by_id', EmployeeMaster::factory()->create()->id)
        ->call('markDelivered')
        ->assertHasNoErrors();

    $fresh = $row->fresh();
    expect($fresh->exited_at)->not->toBeNull()
        ->and($fresh->outward_type)->toBe('final_delivery')
        ->and($fresh->status)->toBe(GateInOut::STATUS_COMPLETED);
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
        ->set('entry_gate_id', GateMaster::factory()->create()->id)
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

/*
|--------------------------------------------------------------------------
| Pickers & defaults (rework chunk 1)
|--------------------------------------------------------------------------
*/

it('defaults entry gate to GATE NO. 1 and exit gate to GATE NO. 2', function () {
    $in = GateMaster::firstOrCreate(['name' => 'GATE NO. 1'], ['is_active' => true]);
    $out = GateMaster::firstOrCreate(['name' => 'GATE NO. 2'], ['is_active' => true]);

    $component = Livewire::test(Edit::class);

    expect($component->get('entry_gate_id'))->toBe($in->id)
        ->and($component->get('exit_gate_id'))->toBe($out->id);
});

it('keeps a saved record\'s own gates when editing', function () {
    GateMaster::firstOrCreate(['name' => 'GATE NO. 1'], ['is_active' => true]);
    $side = GateMaster::factory()->create(['name' => 'SIDE GATE']);
    $row = GateInOut::factory()->create(['entry_gate_id' => $side->id]);

    expect(Livewire::test(Edit::class, ['gateInOut' => $row])->get('entry_gate_id'))->toBe($side->id);
});

it('offers only advisors and cashiers as Delivered By, and only guards as Exit By', function () {
    $advisor = EmployeeMaster::factory()->create(['designation_id' => DesignationMaster::firstOrCreate(['name' => 'MECHANICAL ADVISOR'], ['is_active' => true])->id]);
    $cashier = EmployeeMaster::factory()->create(['designation_id' => DesignationMaster::firstOrCreate(['name' => 'CASHIER'], ['is_active' => true])->id]);
    $guard = EmployeeMaster::factory()->create(['designation_id' => DesignationMaster::firstOrCreate(['name' => 'SECURITY GUARD'], ['is_active' => true])->id]);
    EmployeeMaster::factory()->create(['designation_id' => DesignationMaster::firstOrCreate(['name' => 'TECHNICIAN'], ['is_active' => true])->id]);

    $instance = Livewire::test(Edit::class)->instance();

    expect($instance->deliveryStaff->pluck('id'))->toContain($advisor->id, $cashier->id)
        ->and($instance->deliveryStaff->pluck('id'))->not->toContain($guard->id)
        ->and($instance->securityGuards->pluck('id')->all())->toBe([$guard->id]);
});

it('shows the vehicle name for the selected registration', function () {
    $vehicle = CustomerVehicleMaster::factory()->create();

    $name = Livewire::test(Edit::class)
        ->set('customer_vehicle_id', $vehicle->id)
        ->instance()->linkedVehicleName;

    expect($name)->not->toBeNull();
});

it('stores a captured gate photo on save', function () {
    Storage::fake('public');

    Livewire::test(Edit::class)
        ->set('registration_no', 'GJ 05 AA 9999')
        ->set('entry_gate_id', GateMaster::factory()->create()->id)
        ->set('capturedImage', UploadedFile::fake()->image('gate.jpg'))
        ->call('save')
        ->assertHasNoErrors();

    $row = GateInOut::latest('id')->first();
    expect($row->captured_image_path)->not->toBeNull();
    Storage::disk('public')->assertExists($row->captured_image_path);
});

it('derives completed on save when the delivery stamp already exists', function () {
    $row = GateInOut::factory()->create([
        'exited_at' => now(),
        'status' => GateInOut::STATUS_PENDING,
        'entry_gate_id' => GateMaster::factory()->create()->id,
    ]);

    Livewire::test(Edit::class, ['gateInOut' => $row])
        ->set('notes', 'tidy up')
        ->call('save')
        ->assertHasNoErrors();

    expect($row->fresh()->status)->toBe(GateInOut::STATUS_COMPLETED);
});

it('cancels and restores a visit deliberately', function () {
    $row = GateInOut::factory()->create(['exited_at' => null]);

    $component = Livewire::test(Edit::class, ['gateInOut' => $row])->call('cancelVisit');
    expect($row->fresh()->status)->toBe(GateInOut::STATUS_CANCELLED);

    $component->call('restoreVisit');
    expect($row->fresh()->status)->toBe(GateInOut::STATUS_PENDING);
});

/*
|--------------------------------------------------------------------------
| History listing (rework chunk 3)
|--------------------------------------------------------------------------
*/

it('defaults the history to pending visits', function () {
    GateInOut::factory()->create(['exited_at' => null]);
    GateInOut::factory()->create(['exited_at' => now(), 'status' => GateInOut::STATUS_COMPLETED]);

    $component = Livewire::test(Index::class);

    expect($component->get('statusFilter'))->toBe('pending');
    $component->assertViewHas('rows', fn ($rows) => $rows->total() === 1);
});

it('filters by the outward date range', function () {
    GateInOut::factory()->create(['exited_at' => '2026-08-01 10:00:00', 'status' => GateInOut::STATUS_COMPLETED]);
    GateInOut::factory()->create(['exited_at' => '2026-08-20 10:00:00', 'status' => GateInOut::STATUS_COMPLETED]);

    Livewire::test(Index::class)
        ->set('statusFilter', 'all')
        ->set('outDateFrom', '2026-08-15')
        ->set('outDateTo', '2026-08-31')
        ->assertViewHas('rows', fn ($rows) => $rows->total() === 1);
});

it('sorts by a related name column via subquery', function () {
    $a = EmployeeMaster::factory()->create(['name' => 'AAA GUARD']);
    $z = EmployeeMaster::factory()->create(['name' => 'ZZZ GUARD']);
    GateInOut::factory()->create(['exit_by_id' => $z->id]);
    GateInOut::factory()->create(['exit_by_id' => $a->id]);

    Livewire::test(Index::class)
        ->set('statusFilter', 'all')
        ->call('sort', 'exit_by')
        ->assertViewHas('rows', fn ($rows) => $rows->first()->exitBy->name === 'AAA GUARD');
});

it('fills in the booking automatically when the vehicle has exactly one open', function () {
    $vehicle = CustomerVehicleMaster::factory()->create();
    $appointment = Appointment::factory()->create([
        'customer_vehicle_id' => $vehicle->id,
        'appointment_at' => now()->addHours(2),
    ]);

    Livewire::test(Edit::class)
        ->set('customer_vehicle_id', $vehicle->id)
        ->assertSet('appointment_id', $appointment->id);
});

it('does not guess between two open bookings for the same vehicle', function () {
    $vehicle = CustomerVehicleMaster::factory()->create();

    Appointment::factory()->count(2)->create([
        'customer_vehicle_id' => $vehicle->id,
        'appointment_at' => now()->addHours(2),
    ]);

    // Picking one of two would be the old vehicle-matching heuristic again.
    Livewire::test(Edit::class)
        ->set('customer_vehicle_id', $vehicle->id)
        ->assertSet('appointment_id', null)
        ->tap(fn ($c) => expect($c->instance()->openAppointments)->toHaveCount(2));
});

it('marks the booking arrived once the inward against it is saved', function () {
    $vehicle = CustomerVehicleMaster::factory()->create();
    $appointment = Appointment::factory()->create([
        'customer_vehicle_id' => $vehicle->id,
        'appointment_at' => now()->addHours(2),
    ]);

    Livewire::test(Edit::class)
        ->set('entered_date', now()->format('Y-m-d'))
        ->set('entered_time', now()->format('H:i'))
        ->set('entry_gate_id', GateMaster::factory()->create()->id)
        ->set('customer_vehicle_id', $vehicle->id)
        ->call('save')
        ->assertHasNoErrors();

    expect(GateInOut::latest('id')->first()->appointment_id)->toBe($appointment->id)
        ->and($appointment->fresh()->status)
        ->toBe(Appointment::STATUS_ARRIVED);
});
