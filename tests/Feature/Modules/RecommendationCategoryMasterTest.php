<?php

use App\Modules\RecommendationCategoryMaster\Livewire\Form;
use App\Modules\RecommendationCategoryMaster\Livewire\Index;
use App\Modules\RecommendationCategoryMaster\Models\RecommendationCategoryMaster;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(adminUser());
});

it('renders the index page', function () {
    RecommendationCategoryMaster::factory()->count(3)->create();

    $this->get(route('recommendation-category-master.index'))
        ->assertOk()
        ->assertSeeLivewire(Index::class);
});

it('filters records by search', function () {
    RecommendationCategoryMaster::factory()->create(['name' => 'ALPHA RECORD']);
    RecommendationCategoryMaster::factory()->create(['name' => 'BETA RECORD']);

    // Asserted on the rows, not the rendered page: the form modal's parent
    // picker legitimately lists every category regardless of the search.
    $rows = Livewire::test(Index::class)
        ->set('search', 'ALPHA')
        ->viewData('rows');

    expect($rows->pluck('name')->all())->toBe(['ALPHA RECORD']);
});

it('creates a category via the form, with capital typing', function () {
    Livewire::test(Form::class)
        ->set('name', 'brakes')
        ->set('code', 'brk')
        ->set('sequence_no', 3)
        ->call('save')
        ->assertHasNoErrors()
        ->assertDispatched('recommendation-category-master:saved');

    $record = RecommendationCategoryMaster::firstOrFail();

    expect($record->name)->toBe('BRAKES')
        ->and($record->code)->toBe('BRK')
        ->and($record->sequence_no)->toBe(3)
        ->and($record->parent_id)->toBeNull()
        ->and($record->isSubCategory())->toBeFalse();
});

it('files a sub category under its parent', function () {
    $parent = RecommendationCategoryMaster::factory()->create(['name' => 'BRAKES', 'is_active' => true]);

    Livewire::test(Form::class)
        ->set('parent_id', $parent->id)
        ->set('name', 'front')
        ->call('save')
        ->assertHasNoErrors();

    $sub = RecommendationCategoryMaster::where('name', 'FRONT')->firstOrFail();

    expect($sub->parent_id)->toBe($parent->id)
        ->and($sub->isSubCategory())->toBeTrue()
        ->and($parent->children()->pluck('id')->all())->toBe([$sub->id]);
});

it('nests one level only — a sub category cannot be a parent', function () {
    $parent = RecommendationCategoryMaster::factory()->create(['is_active' => true]);
    $sub = RecommendationCategoryMaster::factory()->under($parent)->create(['is_active' => true]);

    Livewire::test(Form::class)
        ->set('parent_id', $sub->id)
        ->set('name', 'DEEPER')
        ->call('save')
        ->assertHasErrors('parent_id');
});

it('lets two parents each hold a sub category of the same name', function () {
    $brakes = RecommendationCategoryMaster::factory()->create(['is_active' => true]);
    $engine = RecommendationCategoryMaster::factory()->create(['is_active' => true]);
    RecommendationCategoryMaster::factory()->under($brakes)->create(['name' => 'FRONT']);

    Livewire::test(Form::class)
        ->set('parent_id', $engine->id)
        ->set('name', 'FRONT')
        ->call('save')
        ->assertHasNoErrors();

    expect(RecommendationCategoryMaster::where('name', 'FRONT')->count())->toBe(2);
});

it('will not let one parent hold the same name twice', function () {
    $parent = RecommendationCategoryMaster::factory()->create(['is_active' => true]);
    RecommendationCategoryMaster::factory()->under($parent)->create(['name' => 'FRONT']);

    Livewire::test(Form::class)
        ->set('parent_id', $parent->id)
        ->set('name', 'FRONT')
        ->call('save')
        ->assertHasErrors('name');
});

it('separates categories from sub categories by scope', function () {
    $parent = RecommendationCategoryMaster::factory()->create();
    $sub = RecommendationCategoryMaster::factory()->under($parent)->create();

    expect(RecommendationCategoryMaster::categories()->pluck('id')->all())->toBe([$parent->id])
        ->and(RecommendationCategoryMaster::subCategories()->pluck('id')->all())->toBe([$sub->id])
        ->and(RecommendationCategoryMaster::subCategories($parent->id)->pluck('id')->all())->toBe([$sub->id]);
});

it('validates a required name', function () {
    Livewire::test(Form::class)
        ->set('name', '')
        ->call('save')
        ->assertHasErrors('name');
});

it('deletes a category from the index', function () {
    $record = RecommendationCategoryMaster::factory()->create();

    Livewire::test(Index::class)->call('delete', $record->id);

    expect(RecommendationCategoryMaster::find($record->id))->toBeNull();
});

it('takes its sub categories with it when deleted', function () {
    $parent = RecommendationCategoryMaster::factory()->create();
    $sub = RecommendationCategoryMaster::factory()->under($parent)->create();

    Livewire::test(Index::class)->call('delete', $parent->id);

    expect(RecommendationCategoryMaster::find($sub->id))->toBeNull();
});
