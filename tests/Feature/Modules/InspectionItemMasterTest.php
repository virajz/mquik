<?php

use App\Models\User;
use App\Modules\InspectionItemGroupMaster\Models\InspectionItemGroupMaster;
use App\Modules\InspectionItemMaster\Livewire\Form;
use App\Modules\InspectionItemMaster\Livewire\Index;
use App\Modules\InspectionItemMaster\Models\InspectionItemMaster;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(User::factory()->create());
    $this->group = InspectionItemGroupMaster::factory()->create([
        'name' => 'TEST-GROUP',
        'is_active' => true,
    ]);
});

it('renders the index page', function () {
    InspectionItemMaster::factory()->count(3)->create();

    $this->get(route('inspection-item-master.index'))
        ->assertOk()
        ->assertSeeLivewire(Index::class);
});

it('filters records by search', function () {
    InspectionItemMaster::factory()->create(['name' => 'ENGINE OIL LEVEL']);
    InspectionItemMaster::factory()->create(['name' => 'BRAKE PAD THICKNESS']);

    Livewire::test(Index::class)
        ->set('search', 'ENGINE')
        ->assertSee('ENGINE OIL LEVEL')
        ->assertDontSee('BRAKE PAD THICKNESS');
});

it('filters by group', function () {
    $other = InspectionItemGroupMaster::factory()->create(['name' => 'OTHER-GROUP']);
    InspectionItemMaster::factory()->create(['name' => 'ITEM IN TEST-GROUP', 'inspection_item_group_id' => $this->group->id]);
    InspectionItemMaster::factory()->create(['name' => 'ITEM IN OTHER-GROUP', 'inspection_item_group_id' => $other->id]);

    Livewire::test(Index::class)
        ->set('groupFilter', (string) $this->group->id)
        ->assertSee('ITEM IN TEST-GROUP')
        ->assertDontSee('ITEM IN OTHER-GROUP');
});

it('creates with check_type + group fk + capital typing', function () {
    Livewire::test(Form::class)
        ->set('name', 'engine oil level')
        ->set('code', 'eol')
        ->set('inspection_item_group_id', $this->group->id)
        ->set('check_type', 'measurement')
        ->set('measurement_unit', 'mm')
        ->call('save')
        ->assertHasNoErrors()
        ->assertDispatched('inspection-item-master:saved');

    $r = InspectionItemMaster::firstOrFail();
    expect($r->name)->toBe('ENGINE OIL LEVEL')
        ->and($r->code)->toBe('EOL')
        ->and($r->check_type)->toBe('measurement')
        ->and($r->measurement_unit)->toBe('MM')
        ->and($r->inspection_item_group_id)->toBe($this->group->id)
        ->and($r->is_active)->toBeTrue();
});

it('rejects unknown check_type', function () {
    Livewire::test(Form::class)
        ->set('name', 'TEST')
        ->set('check_type', 'invalid')
        ->call('save')
        ->assertHasErrors(['check_type']);
});

it('rejects unknown group fk', function () {
    Livewire::test(Form::class)
        ->set('name', 'TEST')
        ->set('check_type', 'visual')
        ->set('inspection_item_group_id', 99999)
        ->call('save')
        ->assertHasErrors(['inspection_item_group_id']);
});

it('blocks duplicate name within same group', function () {
    InspectionItemMaster::factory()->create([
        'name' => 'SAME NAME',
        'inspection_item_group_id' => $this->group->id,
    ]);

    Livewire::test(Form::class)
        ->set('name', 'SAME NAME')
        ->set('check_type', 'visual')
        ->set('inspection_item_group_id', $this->group->id)
        ->call('save')
        ->assertHasErrors(['name']);
});

it('allows duplicate name across different groups', function () {
    $other = InspectionItemGroupMaster::factory()->create(['name' => 'OTHER-GROUP-2']);
    InspectionItemMaster::factory()->create([
        'name' => 'SHARED NAME',
        'inspection_item_group_id' => $this->group->id,
    ]);

    Livewire::test(Form::class)
        ->set('name', 'SHARED NAME')
        ->set('check_type', 'visual')
        ->set('inspection_item_group_id', $other->id)
        ->call('save')
        ->assertHasNoErrors();
});

it('updates an existing item', function () {
    $r = InspectionItemMaster::factory()->create(['name' => 'OLD NAME']);

    Livewire::test(Form::class)
        ->dispatch('inspection-item-master:edit', id: $r->id)
        ->set('name', 'updated')
        ->call('save')
        ->assertHasNoErrors();

    expect($r->fresh()->name)->toBe('UPDATED');
});

it('deletes an item from the index', function () {
    $r = InspectionItemMaster::factory()->create();
    Livewire::test(Index::class)->call('delete', $r->id);
    expect(InspectionItemMaster::find($r->id))->toBeNull();
});

it('requires authentication', function () {
    auth()->logout();
    $this->get(route('inspection-item-master.index'))->assertRedirect(route('login'));
});
