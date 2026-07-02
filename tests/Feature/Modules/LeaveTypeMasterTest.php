<?php

use App\Modules\LeaveTypeMaster\Livewire\Form;
use App\Modules\LeaveTypeMaster\Livewire\Index;
use App\Modules\LeaveTypeMaster\Models\LeaveTypeMaster;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(adminUser());
});

it('renders the index page', function () {
    LeaveTypeMaster::factory()->count(3)->create();

    $this->get(route('leave-type-master.index'))
        ->assertOk()
        ->assertSeeLivewire(Index::class);
});

it('requires authentication', function () {
    auth()->logout();
    $this->get(route('leave-type-master.index'))->assertRedirect(route('login'));
});

it('searches by name or code (case-insensitive)', function () {
    LeaveTypeMaster::factory()->create(['name' => 'BEREAVEMENT LEAVE', 'code' => 'BRV']);
    LeaveTypeMaster::factory()->create(['name' => 'SABBATICAL', 'code' => 'SAB']);

    Livewire::test(Index::class)->set('search', 'bereavement')
        ->assertSee('BEREAVEMENT LEAVE')->assertDontSee('SABBATICAL');
    Livewire::test(Index::class)->set('search', 'sab')
        ->assertSee('SABBATICAL')->assertDontSee('BEREAVEMENT LEAVE');
});

it('creates a leave type, uppercasing name', function () {
    Livewire::test(Form::class)
        ->set('name', 'casual leave')->set('code', 'cl')
        ->call('save')->assertHasNoErrors()
        ->assertDispatched('leave-type-master:saved');

    expect(LeaveTypeMaster::firstOrFail()->name)->toBe('CASUAL LEAVE');
});

it('validates name is required and unique', function () {
    LeaveTypeMaster::factory()->create(['name' => 'EXISTING']);
    Livewire::test(Form::class)->set('name', '')->call('save')->assertHasErrors(['name' => 'required']);
    Livewire::test(Form::class)->set('name', 'EXISTING')->call('save')->assertHasErrors(['name' => 'unique']);
});

it('deletes a leave type from the index', function () {
    $record = LeaveTypeMaster::factory()->create();
    Livewire::test(Index::class)->call('delete', $record->id);
    expect(LeaveTypeMaster::find($record->id))->toBeNull();
});
