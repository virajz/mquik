<?php

use App\Modules\RefundTypeMaster\Livewire\Form;
use App\Modules\RefundTypeMaster\Livewire\Index;
use App\Modules\RefundTypeMaster\Models\RefundTypeMaster;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(adminUser());
});

it('renders the index page', function () {
    RefundTypeMaster::factory()->count(3)->create();

    $this->get(route('refund-type-master.index'))
        ->assertOk()
        ->assertSeeLivewire(Index::class);
});

it('requires authentication', function () {
    auth()->logout();
    $this->get(route('refund-type-master.index'))->assertRedirect(route('login'));
});

it('searches by name or code (case-insensitive)', function () {
    RefundTypeMaster::factory()->create(['name' => 'MISC ADJUST', 'code' => 'MADJ']);
    RefundTypeMaster::factory()->create(['name' => 'GOODWILL CREDIT', 'code' => 'GWC']);

    Livewire::test(Index::class)->set('search', 'misc')
        ->assertSee('MISC ADJUST')
        ->assertDontSee('GOODWILL CREDIT');

    Livewire::test(Index::class)->set('search', 'gwc')
        ->assertSee('GOODWILL CREDIT')
        ->assertDontSee('MISC ADJUST');
});

it('creates a type, uppercasing name', function () {
    Livewire::test(Form::class)
        ->set('name', 'excess payment')
        ->set('code', 'excess')
        ->call('save')
        ->assertHasNoErrors()
        ->assertDispatched('refund-type-master:saved');

    expect(RefundTypeMaster::firstOrFail()->name)->toBe('EXCESS PAYMENT');
});

it('validates name is required and unique', function () {
    RefundTypeMaster::factory()->create(['name' => 'EXISTING']);

    Livewire::test(Form::class)->set('name', '')->call('save')->assertHasErrors(['name' => 'required']);
    Livewire::test(Form::class)->set('name', 'EXISTING')->call('save')->assertHasErrors(['name' => 'unique']);
});

it('deletes a type from the index', function () {
    $record = RefundTypeMaster::factory()->create();

    Livewire::test(Index::class)->call('delete', $record->id);

    expect(RefundTypeMaster::find($record->id))->toBeNull();
});
