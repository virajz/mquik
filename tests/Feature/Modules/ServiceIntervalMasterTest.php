<?php

use App\Modules\ServiceIntervalMaster\Livewire\Form;
use App\Modules\ServiceIntervalMaster\Livewire\Index;
use App\Modules\ServiceIntervalMaster\Models\ServiceIntervalMaster;
use Livewire\Livewire;

beforeEach(function () {
    // The form authorises, so the acting user needs the module's permissions.
    $this->actingAs(adminUser());
});

it('renders the index page', function () {
    ServiceIntervalMaster::factory()->count(3)->create();

    $this->get(route('service-interval-master.index'))
        ->assertOk()
        ->assertSeeLivewire(Index::class);
});

it('requires authentication', function () {
    auth()->logout();
    $this->get(route('service-interval-master.index'))->assertRedirect(route('login'));
});

it('filters records by search', function () {
    ServiceIntervalMaster::factory()->create(['name' => 'ALPHA SERVICE']);
    ServiceIntervalMaster::factory()->create(['name' => 'BETA SERVICE']);

    Livewire::test(Index::class)
        ->set('search', 'ALPHA')
        ->assertSee('ALPHA SERVICE')
        ->assertDontSee('BETA SERVICE');
});

it('filters by active status', function () {
    ServiceIntervalMaster::factory()->create(['name' => 'ENABLED ONE']);
    ServiceIntervalMaster::factory()->inactive()->create(['name' => 'DISABLED TWO']);

    Livewire::test(Index::class)
        ->set('statusFilter', 'inactive')
        ->assertSee('DISABLED TWO')
        ->assertDontSee('ENABLED ONE');
});

it('sorts by the kilometre interval', function () {
    ServiceIntervalMaster::factory()->create(['name' => 'SHORT ONE', 'interval_km' => 5000]);
    ServiceIntervalMaster::factory()->create(['name' => 'LONG ONE', 'interval_km' => 40000]);

    $html = Livewire::test(Index::class)->call('sort', 'interval_km')->html();

    expect(strpos($html, 'SHORT ONE'))->toBeLessThan(strpos($html, 'LONG ONE'));
});

it('creates an interval with both bounds', function () {
    Livewire::test(Form::class)
        ->set('name', 'engine oil replace')
        ->set('interval_months', 6)
        ->set('interval_km', 10000)
        ->call('save')
        ->assertHasNoErrors()
        ->assertDispatched('service-interval-master:saved');

    $row = ServiceIntervalMaster::first();

    expect($row->name)->toBe('ENGINE OIL REPLACE')   // capital typing enforced
        ->and($row->interval_months)->toBe(6)
        ->and($row->interval_km)->toBe(10000)
        ->and($row->label())->toBe('6 months / 10,000 km');
});

it('allows an interval with neither bound — some jobs are never due', function () {
    Livewire::test(Form::class)
        ->set('name', 'CAR WASH')
        ->call('save')
        ->assertHasNoErrors();

    $row = ServiceIntervalMaster::first();

    expect($row->hasBound())->toBeFalse()
        ->and($row->label())->toBe('No interval');
});

it('rejects a duplicate service name', function () {
    ServiceIntervalMaster::factory()->create(['name' => 'PMS']);

    Livewire::test(Form::class)
        ->set('name', 'PMS')
        ->call('save')
        ->assertHasErrors(['name']);
});

it('keeps its own name when editing', function () {
    $row = ServiceIntervalMaster::factory()->create(['name' => 'PMS']);

    Livewire::test(Form::class)
        ->dispatch('service-interval-master:edit', id: $row->id)
        ->set('interval_months', 9)
        ->call('save')
        ->assertHasNoErrors();

    expect($row->fresh()->interval_months)->toBe(9);
});

it('validates the interval bounds', function () {
    Livewire::test(Form::class)
        ->set('name', 'SOMETHING')
        ->set('interval_months', 999)
        ->set('interval_km', 5)
        ->call('save')
        ->assertHasErrors(['interval_months', 'interval_km']);
});

it('deletes an interval from the index', function () {
    $row = ServiceIntervalMaster::factory()->create();

    Livewire::test(Index::class)->call('delete', $row->id);

    expect(ServiceIntervalMaster::find($row->id))->toBeNull();
});
