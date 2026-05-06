<?php

use App\Models\User;
use App\Modules\InspectionItemGroupMaster\Models\InspectionItemGroupMaster;
use App\Modules\InspectionItemMaster\Models\InspectionItemMaster;
use App\Modules\InspectionTemplateMaster\Livewire\Form;
use App\Modules\InspectionTemplateMaster\Livewire\Index;
use App\Modules\InspectionTemplateMaster\Models\InspectionTemplateMaster;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(User::factory()->create());
    $this->group = InspectionItemGroupMaster::factory()->create([
        'name' => 'TPL-TEST-GROUP',
        'is_active' => true,
    ]);
    $this->itemA = InspectionItemMaster::factory()->create([
        'name' => 'TPL ITEM ALPHA',
        'inspection_item_group_id' => $this->group->id,
    ]);
    $this->itemB = InspectionItemMaster::factory()->create([
        'name' => 'TPL ITEM BETA',
        'inspection_item_group_id' => $this->group->id,
    ]);
    $this->itemC = InspectionItemMaster::factory()->create([
        'name' => 'TPL ITEM GAMMA',
        'inspection_item_group_id' => $this->group->id,
    ]);
});

it('renders the index page', function () {
    InspectionTemplateMaster::factory()->count(3)->create();

    $this->get(route('inspection-template-master.index'))
        ->assertOk()
        ->assertSeeLivewire(Index::class);
});

it('filters records by search', function () {
    InspectionTemplateMaster::factory()->create(['name' => 'PMS STANDARD']);
    InspectionTemplateMaster::factory()->create(['name' => 'TYRE SERVICE']);

    Livewire::test(Index::class)
        ->set('search', 'PMS')
        ->assertSee('PMS STANDARD')
        ->assertDontSee('TYRE SERVICE');
});

it('filters by applies_to', function () {
    InspectionTemplateMaster::factory()->appliesTo('pms')->create(['name' => 'PMS TPL']);
    InspectionTemplateMaster::factory()->appliesTo('tyre')->create(['name' => 'TYRE TPL']);

    Livewire::test(Index::class)
        ->set('appliesToFilter', 'pms')
        ->assertSee('PMS TPL')
        ->assertDontSee('TYRE TPL');
});

it('creates with applies_to and persists pivot items', function () {
    Livewire::test(Form::class)
        ->set('name', 'pms standard')
        ->set('code', 'pms-std')
        ->set('applies_to', 'pms')
        ->set('selected_item_ids', [$this->itemA->id, $this->itemB->id])
        ->call('save')
        ->assertHasNoErrors()
        ->assertDispatched('inspection-template-master:saved');

    $r = InspectionTemplateMaster::firstOrFail();
    expect($r->name)->toBe('PMS STANDARD')
        ->and($r->code)->toBe('PMS-STD')
        ->and($r->applies_to)->toBe('pms')
        ->and($r->is_active)->toBeTrue()
        ->and($r->items()->count())->toBe(2)
        ->and($r->items()->pluck('inspection_items.id')->all())->toBe([$this->itemA->id, $this->itemB->id]);
});

it('updates an existing template and changes pivot items', function () {
    $r = InspectionTemplateMaster::factory()->create(['name' => 'OLD TEMPLATE']);
    $r->items()->sync([
        $this->itemA->id => ['position' => 1, 'is_required' => true],
        $this->itemB->id => ['position' => 2, 'is_required' => true],
    ]);

    Livewire::test(Form::class)
        ->dispatch('inspection-template-master:edit', id: $r->id)
        ->set('name', 'updated template')
        ->set('selected_item_ids', [$this->itemB->id, $this->itemC->id])
        ->call('save')
        ->assertHasNoErrors();

    $fresh = $r->fresh();
    expect($fresh->name)->toBe('UPDATED TEMPLATE')
        ->and($fresh->items()->pluck('inspection_items.id')->all())->toBe([$this->itemB->id, $this->itemC->id]);
});

it('returns correct items count via withCount', function () {
    $r = InspectionTemplateMaster::factory()->create(['name' => 'WITHCOUNT TPL']);
    $r->items()->sync([
        $this->itemA->id => ['position' => 1, 'is_required' => true],
        $this->itemB->id => ['position' => 2, 'is_required' => true],
        $this->itemC->id => ['position' => 3, 'is_required' => true],
    ]);

    $row = InspectionTemplateMaster::query()->withCount('items')->find($r->id);

    expect($row->items_count)->toBe(3);
});

it('rejects unknown applies_to', function () {
    Livewire::test(Form::class)
        ->set('name', 'TEST')
        ->set('applies_to', 'invalid')
        ->call('save')
        ->assertHasErrors(['applies_to']);
});

it('blocks duplicate template name', function () {
    InspectionTemplateMaster::factory()->create(['name' => 'EXISTING TPL']);

    Livewire::test(Form::class)
        ->set('name', 'EXISTING TPL')
        ->set('applies_to', 'custom')
        ->call('save')
        ->assertHasErrors(['name']);
});

it('deletes a template from the index', function () {
    $r = InspectionTemplateMaster::factory()->create();
    Livewire::test(Index::class)->call('delete', $r->id);
    expect(InspectionTemplateMaster::find($r->id))->toBeNull();
});

it('requires authentication', function () {
    auth()->logout();
    $this->get(route('inspection-template-master.index'))->assertRedirect(route('login'));
});
