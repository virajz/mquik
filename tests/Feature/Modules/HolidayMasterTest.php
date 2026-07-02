<?php

use App\Modules\HolidayMaster\Livewire\Form;
use App\Modules\HolidayMaster\Livewire\Index;
use App\Modules\HolidayMaster\Models\HolidayMaster;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(adminUser());
});

it('renders the index page', function () {
    HolidayMaster::factory()->count(3)->create();

    $this->get(route('holiday-master.index'))
        ->assertOk()
        ->assertSeeLivewire(Index::class);
});

it('requires authentication', function () {
    auth()->logout();
    $this->get(route('holiday-master.index'))->assertRedirect(route('login'));
});

it('creates a holiday with type, date and recurrence', function () {
    Livewire::test(Form::class)
        ->set('name', 'republic day')
        ->set('code', 'rd')
        ->set('holiday_type', 'national')
        ->set('holiday_date', '2026-01-26')
        ->set('is_recurring', true)
        ->call('save')
        ->assertHasNoErrors()
        ->assertDispatched('holiday-master:saved');

    $h = HolidayMaster::firstOrFail();
    expect($h->name)->toBe('REPUBLIC DAY')
        ->and($h->holiday_type)->toBe('national')
        ->and($h->holiday_date->format('Y-m-d'))->toBe('2026-01-26')
        ->and($h->is_recurring)->toBeTrue();
});

it('validates holiday_type against the allowed set', function () {
    Livewire::test(Form::class)
        ->set('name', 'bad day')
        ->set('holiday_type', 'nonsense')
        ->call('save')
        ->assertHasErrors(['holiday_type']);
});

it('deletes a holiday from the index', function () {
    $record = HolidayMaster::factory()->create();
    Livewire::test(Index::class)->call('delete', $record->id);
    expect(HolidayMaster::find($record->id))->toBeNull();
});
