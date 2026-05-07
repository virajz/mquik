<?php

use App\Modules\ChecklistGroupMaster\Models\ChecklistGroupMaster;
use App\Modules\ChecklistTemplateMaster\Livewire\Form;
use App\Modules\ChecklistTemplateMaster\Livewire\Index;
use App\Modules\ChecklistTemplateMaster\Models\ChecklistTemplateMaster;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(adminUser());
    $this->group = ChecklistGroupMaster::factory()->create([
        'name' => 'TPL-TEST-GROUP',
        'is_active' => true,
    ]);
    $this->otherGroup = ChecklistGroupMaster::factory()->create([
        'name' => 'TPL-OTHER-GROUP',
        'is_active' => true,
    ]);
});

it('renders the index page', function () {
    ChecklistTemplateMaster::factory()->forGroup($this->group)->count(3)->create();

    $this->get(route('checklist-template-master.index'))
        ->assertOk()
        ->assertSeeLivewire(Index::class);
});

it('filters records by search', function () {
    ChecklistTemplateMaster::factory()->forGroup($this->group)->create(['name' => 'PMS STANDARD']);
    ChecklistTemplateMaster::factory()->forGroup($this->group)->create(['name' => 'TYRE SERVICE']);

    Livewire::test(Index::class)
        ->set('search', 'PMS')
        ->assertSee('PMS STANDARD')
        ->assertDontSee('TYRE SERVICE');
});

it('filters by group', function () {
    $a = ChecklistTemplateMaster::factory()->forGroup($this->group)->create(['name' => 'GRP A TPL']);
    $b = ChecklistTemplateMaster::factory()->forGroup($this->otherGroup)->create(['name' => 'GRP B TPL']);

    Livewire::test(Index::class)
        ->set('groupFilter', (string) $this->group->id)
        ->assertSee('GRP A TPL')
        ->assertDontSee('GRP B TPL');
});

it('filters by applies_to', function () {
    ChecklistTemplateMaster::factory()->forGroup($this->group)->appliesTo('claim')->create(['name' => 'CLAIM TPL']);
    ChecklistTemplateMaster::factory()->forGroup($this->group)->appliesTo('delivery')->create(['name' => 'DELIVERY TPL']);

    Livewire::test(Index::class)
        ->set('appliesToFilter', 'claim')
        ->assertSee('CLAIM TPL')
        ->assertDontSee('DELIVERY TPL');
});

it('filters by active status', function () {
    ChecklistTemplateMaster::factory()->forGroup($this->group)->create(['name' => 'TPL ENABLED']);
    ChecklistTemplateMaster::factory()->forGroup($this->group)->inactive()->create(['name' => 'TPL DISABLED']);

    Livewire::test(Index::class)->set('statusFilter', 'active')
        ->assertSee('TPL ENABLED')
        ->assertDontSee('TPL DISABLED');

    Livewire::test(Index::class)->set('statusFilter', 'inactive')
        ->assertSee('TPL DISABLED')
        ->assertDontSee('TPL ENABLED');
});

it('creates a template with items array and capitalizes name', function () {
    Livewire::test(Form::class)
        ->set('name', 'document collection standard')
        ->set('code', 'doc-std')
        ->set('checklist_group_id', $this->group->id)
        ->set('applies_to', 'claim')
        ->set('items', [
            ['label' => 'rc copy', 'is_required' => true],
            ['label' => 'dl copy', 'is_required' => true],
            ['label' => 'cancel cheque', 'is_required' => false],
        ])
        ->call('save')
        ->assertHasNoErrors()
        ->assertDispatched('checklist-template-master:saved');

    $r = ChecklistTemplateMaster::firstOrFail();
    expect($r->name)->toBe('DOCUMENT COLLECTION STANDARD')
        ->and($r->code)->toBe('DOC-STD')
        ->and($r->checklist_group_id)->toBe($this->group->id)
        ->and($r->applies_to)->toBe('claim')
        ->and($r->is_active)->toBeTrue()
        ->and(count($r->items))->toBe(3)
        ->and($r->items[0]['label'])->toBe('RC COPY')
        ->and($r->items[0]['is_required'])->toBeTrue()
        ->and($r->items[2]['label'])->toBe('CANCEL CHEQUE')
        ->and($r->items[2]['is_required'])->toBeFalse();
});

it('updates an existing template', function () {
    $r = ChecklistTemplateMaster::factory()->forGroup($this->group)->create([
        'name' => 'OLD TEMPLATE',
        'items' => [
            ['label' => 'OLD ITEM', 'is_required' => true],
        ],
    ]);

    Livewire::test(Form::class)
        ->dispatch('checklist-template-master:edit', id: $r->id)
        ->set('name', 'updated template')
        ->set('items', [
            ['label' => 'new item one', 'is_required' => true],
            ['label' => 'new item two', 'is_required' => false],
        ])
        ->call('save')
        ->assertHasNoErrors();

    $fresh = $r->fresh();
    expect($fresh->name)->toBe('UPDATED TEMPLATE')
        ->and(count($fresh->items))->toBe(2)
        ->and($fresh->items[0]['label'])->toBe('NEW ITEM ONE')
        ->and($fresh->items[1]['is_required'])->toBeFalse();
});

it('deletes a template from the index', function () {
    $r = ChecklistTemplateMaster::factory()->forGroup($this->group)->create();
    Livewire::test(Index::class)->call('delete', $r->id);
    expect(ChecklistTemplateMaster::find($r->id))->toBeNull();
});

it('requires name, group, applies_to, and at least one item', function () {
    Livewire::test(Form::class)
        ->set('name', '')
        ->set('checklist_group_id', null)
        ->set('applies_to', '')
        ->set('items', [])
        ->call('save')
        ->assertHasErrors(['name', 'checklist_group_id', 'applies_to', 'items']);
});

it('blocks duplicate template name within the same group', function () {
    ChecklistTemplateMaster::factory()->forGroup($this->group)->create(['name' => 'EXISTING TPL']);

    // Same name in same group → blocked
    Livewire::test(Form::class)
        ->set('name', 'EXISTING TPL')
        ->set('checklist_group_id', $this->group->id)
        ->set('applies_to', 'generic')
        ->set('items', [['label' => 'X', 'is_required' => true]])
        ->call('save')
        ->assertHasErrors(['name']);

    // Same name in a different group → allowed
    Livewire::test(Form::class)
        ->set('name', 'EXISTING TPL')
        ->set('checklist_group_id', $this->otherGroup->id)
        ->set('applies_to', 'generic')
        ->set('items', [['label' => 'X', 'is_required' => true]])
        ->call('save')
        ->assertHasNoErrors();
});

it('addItem and removeItem actions update the items array', function () {
    $component = Livewire::test(Form::class);

    // Mount initializes 1 row
    expect(count($component->get('items')))->toBe(1);

    $component->call('addItem')->call('addItem');
    expect(count($component->get('items')))->toBe(3);

    $component->call('removeItem', 1);
    expect(count($component->get('items')))->toBe(2);
});

it('requires authentication', function () {
    auth()->logout();
    $this->get(route('checklist-template-master.index'))->assertRedirect(route('login'));
});
