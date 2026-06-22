<?php

use App\Modules\PartTypeMaster part-type-master\Livewire\Form;
use App\Modules\PartTypeMaster part-type-master\Livewire\Index;
use App\Modules\PartTypeMaster part-type-master\Models\PartTypeMaster part-type-master;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(adminUser());
});

it('renders the index page', function () {
    PartTypeMaster part-type-master::factory()->count(3)->create();

    $this->get(route('.index'))
        ->assertOk()
        ->assertSeeLivewire(Index::class);
});

it('searches by name or code', function () {
    PartTypeMaster part-type-master::factory()->create(['name' => 'ENGINEEE NOISE', 'code' => 'ENG']);
    PartTypeMaster part-type-master::factory()->create(['name' => 'BRAKEEE PROBLEM', 'code' => 'BRK']);

    Livewire::test(Index::class)->set('search', 'ENGINEEE')
        ->assertSee('ENGINEEE NOISE')
        ->assertDontSee('BRAKEEE PROBLEM');

    Livewire::test(Index::class)->set('search', 'brk') // case-insensitive via whereLike
        ->assertSee('BRAKEEE PROBLEM')
        ->assertDontSee('ENGINEEE NOISE');
});

it('filters by active status', function () {
    PartTypeMaster part-type-master::factory()->create(['name' => 'ENGINE ENABLED']);
    PartTypeMaster part-type-master::factory()->inactive()->create(['name' => 'BRAKE DISABLED']);

    Livewire::test(Index::class)->set('statusFilter', 'active')
        ->assertSee('ENGINE ENABLED')
        ->assertDontSee('BRAKE DISABLED');

    Livewire::test(Index::class)->set('statusFilter', 'inactive')
        ->assertSee('BRAKE DISABLED')
        ->assertDontSee('ENGINE ENABLED');
});

it('sorts by name alphabetically by default', function () {
    PartTypeMaster part-type-master::factory()->create(['name' => 'ZEBRA TYPE']);
    PartTypeMaster part-type-master::factory()->create(['name' => 'ALPHA TYPE']);
    PartTypeMaster part-type-master::factory()->create(['name' => 'MIDDLE TYPE']);

    $html = Livewire::test(Index::class)->html();

    $alphaPos = strpos($html, 'ALPHA TYPE');
    $middlePos = strpos($html, 'MIDDLE TYPE');
    $zebraPos = strpos($html, 'ZEBRA TYPE');

    expect($alphaPos)->toBeLessThan($middlePos)
        ->and($middlePos)->toBeLessThan($zebraPos);
});

it('creates a type with code', function () {
    Livewire::test(Form::class)
        ->set('name', 'engine noise')
        ->set('code', 'eng')
        ->call('save')
        ->assertHasNoErrors()
        ->assertDispatched(':saved');

    $record = PartTypeMaster part-type-master::firstOrFail();
    expect($record->name)->toBe('ENGINE NOISE')
        ->and($record->code)->toBe('ENG')
        ->and($record->is_active)->toBeTrue();
});

it('updates an existing type', function () {
    $record = PartTypeMaster part-type-master::factory()->create(['name' => 'OLD NAME']);

    Livewire::test(Form::class)
        ->dispatch(':edit', id: $record->id)
        ->set('name', 'updated name')
        ->call('save')
        ->assertHasNoErrors();

    expect($record->fresh()->name)->toBe('UPDATED NAME');
});

it('deletes a type from the index', function () {
    $record = PartTypeMaster part-type-master::factory()->create();

    Livewire::test(Index::class)->call('delete', $record->id);

    expect(PartTypeMaster part-type-master::find($record->id))->toBeNull();
});

it('validates name is required and unique', function () {
    PartTypeMaster part-type-master::factory()->create(['name' => 'EXISTING']);

    Livewire::test(Form::class)
        ->set('name', '')
        ->call('save')
        ->assertHasErrors(['name' => 'required']);

    Livewire::test(Form::class)
        ->set('name', 'EXISTING')
        ->call('save')
        ->assertHasErrors(['name' => 'unique']);
});

it('allows updating a type without triggering self-uniqueness conflict', function () {
    $record = PartTypeMaster part-type-master::factory()->create(['name' => 'ENGINE NOISE']);

    Livewire::test(Form::class)
        ->dispatch(':edit', id: $record->id)
        ->set('name', 'ENGINE NOISE') // same name as the record being edited
        ->call('save')
        ->assertHasNoErrors();
});

it('requires authentication', function () {
    auth()->logout();

    $this->get(route('.index'))->assertRedirect(route('login'));
});
