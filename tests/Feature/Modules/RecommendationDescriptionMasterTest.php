<?php

use App\Modules\RecommendationCategoryMaster\Models\RecommendationCategoryMaster;
use App\Modules\RecommendationDescriptionMaster\Livewire\Form;
use App\Modules\RecommendationDescriptionMaster\Livewire\Index;
use App\Modules\RecommendationDescriptionMaster\Models\RecommendationDescriptionMaster;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(adminUser());
});

it('renders the index page', function () {
    RecommendationDescriptionMaster::factory()->count(3)->create();

    $this->get(route('recommendation-description-master.index'))
        ->assertOk()
        ->assertSeeLivewire(Index::class);
});

it('filters records by search', function () {
    $category = RecommendationCategoryMaster::factory()->create();
    RecommendationDescriptionMaster::factory()->create(['name' => 'REPLACE PADS', 'category_id' => $category->id]);
    RecommendationDescriptionMaster::factory()->create(['name' => 'TOP UP OIL', 'category_id' => $category->id]);

    $rows = Livewire::test(Index::class)->set('search', 'PADS')->viewData('rows');

    expect($rows->pluck('name')->all())->toBe(['REPLACE PADS']);
});

it('finds a description by its category name', function () {
    $brakes = RecommendationCategoryMaster::factory()->create(['name' => 'BRAKES']);
    $engine = RecommendationCategoryMaster::factory()->create(['name' => 'ENGINE']);
    RecommendationDescriptionMaster::factory()->create(['name' => 'ALPHA', 'category_id' => $brakes->id]);
    RecommendationDescriptionMaster::factory()->create(['name' => 'BETA', 'category_id' => $engine->id]);

    $rows = Livewire::test(Index::class)->set('search', 'BRAKES')->viewData('rows');

    expect($rows->pluck('name')->all())->toBe(['ALPHA']);
});

it('creates a description under a category, with capital typing', function () {
    $category = RecommendationCategoryMaster::factory()->create(['is_active' => true]);

    Livewire::test(Form::class)
        ->set('category_id', $category->id)
        ->set('name', 'replace front pads')
        ->set('sequence_no', 2)
        ->call('save')
        ->assertHasNoErrors()
        ->assertDispatched('recommendation-description-master:saved');

    $record = RecommendationDescriptionMaster::firstOrFail();

    expect($record->name)->toBe('REPLACE FRONT PADS')
        ->and($record->category_id)->toBe($category->id)
        ->and($record->sub_category_id)->toBeNull()
        ->and($record->sequence_no)->toBe(2);
});

it('requires a category', function () {
    Livewire::test(Form::class)
        ->set('name', 'ORPHANED')
        ->call('save')
        ->assertHasErrors('category_id');
});

it('refuses a sub category belonging to a different category', function () {
    $brakes = RecommendationCategoryMaster::factory()->create(['is_active' => true]);
    $engine = RecommendationCategoryMaster::factory()->create(['is_active' => true]);
    $front = RecommendationCategoryMaster::factory()->under($brakes)->create(['is_active' => true]);

    Livewire::test(Form::class)
        ->set('category_id', $engine->id)
        ->set('sub_category_id', $front->id)
        ->set('name', 'MISFILED')
        ->call('save')
        ->assertHasErrors('sub_category_id');
});

it('clears the sub category when the category changes under it', function () {
    $brakes = RecommendationCategoryMaster::factory()->create(['is_active' => true]);
    $engine = RecommendationCategoryMaster::factory()->create(['is_active' => true]);
    $front = RecommendationCategoryMaster::factory()->under($brakes)->create(['is_active' => true]);

    $component = Livewire::test(Form::class)
        ->set('category_id', $brakes->id)
        ->set('sub_category_id', $front->id)
        ->set('category_id', $engine->id);

    expect($component->get('sub_category_id'))->toBeNull();
});

it('offers only the chosen category\'s sub categories', function () {
    $brakes = RecommendationCategoryMaster::factory()->create(['is_active' => true]);
    $engine = RecommendationCategoryMaster::factory()->create(['is_active' => true]);
    $front = RecommendationCategoryMaster::factory()->under($brakes)->create(['is_active' => true]);
    $head = RecommendationCategoryMaster::factory()->under($engine)->create(['is_active' => true]);

    $offered = Livewire::test(Form::class)
        ->set('category_id', $brakes->id)
        ->instance()
        ->subCategories
        ->modelKeys();

    expect($offered)->toContain($front->id)->not->toContain($head->id);
});

it('lets the same wording live under two different categories', function () {
    $brakes = RecommendationCategoryMaster::factory()->create(['is_active' => true]);
    $engine = RecommendationCategoryMaster::factory()->create(['is_active' => true]);
    RecommendationDescriptionMaster::factory()->create(['name' => 'INSPECT AGAIN', 'category_id' => $brakes->id]);

    Livewire::test(Form::class)
        ->set('category_id', $engine->id)
        ->set('name', 'INSPECT AGAIN')
        ->call('save')
        ->assertHasNoErrors();

    expect(RecommendationDescriptionMaster::where('name', 'INSPECT AGAIN')->count())->toBe(2);
});

it('will not repeat the same wording inside one category', function () {
    $category = RecommendationCategoryMaster::factory()->create(['is_active' => true]);
    RecommendationDescriptionMaster::factory()->create(['name' => 'REPLACE PADS', 'category_id' => $category->id]);

    Livewire::test(Form::class)
        ->set('category_id', $category->id)
        ->set('name', 'REPLACE PADS')
        ->call('save')
        ->assertHasErrors('name');
});

it('deletes a description from the index', function () {
    $record = RecommendationDescriptionMaster::factory()->create();

    Livewire::test(Index::class)->call('delete', $record->id);

    expect(RecommendationDescriptionMaster::find($record->id))->toBeNull();
});
