<?php

use App\Modules\Consumable\Livewire\Edit;
use App\Modules\Consumable\Livewire\Index;
use App\Modules\Consumable\Models\Consumable;
use App\Modules\ConsumableCategoryMaster\Models\ConsumableCategoryMaster;
use App\Modules\Inventory\Models\StockEntry;
use App\Modules\Inventory\Services\StockLedger;
use App\Modules\SpareMaster\Models\SpareMaster;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(adminUser());
});

it('renders the index page', function () {
    Consumable::factory()->count(2)->create();

    $this->get(route('consumable.index'))
        ->assertOk()
        ->assertSeeLivewire(Index::class);
});

it('requires authentication', function () {
    auth()->logout();
    $this->get(route('consumable.index'))->assertRedirect(route('login'));
});

it('creates a consumable, stamps CN number, and redirects into the editor', function () {
    $category = ConsumableCategoryMaster::factory()->create();

    Livewire::test(Edit::class)
        ->set('consumable_category_id', $category->id)
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect();

    $cn = Consumable::firstOrFail();
    expect($cn->consumable_no)->toBe('CN-'.str_pad((string) $cn->id, 5, '0', STR_PAD_LEFT))
        ->and($cn->consumable_category_id)->toBe($category->id)
        ->and($cn->consumed_at)->not->toBeNull();
});

it('auto-deducts spare-linked lines from stock and re-syncs on edit', function () {
    $spare = SpareMaster::factory()->create();
    StockEntry::create([
        'spare_id' => $spare->id, 'entry_type' => StockEntry::TYPE_OPENING, 'qty' => 100, 'rate_per_unit' => 50, 'moved_at' => now(),
    ]);
    $cn = Consumable::factory()->create();

    $component = Livewire::test(Edit::class, ['consumable' => $cn])
        ->set('items', [
            ['id' => null, 'line_type' => 'spare', 'spare_id' => $spare->id, 'labour_id' => null, 'uom_id' => null, 'tax_id' => null, 'description' => 'grease', 'hsn_code' => null, 'qty' => 10, 'unit_rate' => 50, 'tax_percent' => 0, 'sequence_no' => 1],
        ])
        ->call('save')
        ->assertHasNoErrors();

    expect(StockLedger::currentQty($spare->id))->toBe(90.0); // 100 - 10 consumed

    // Re-save with 4 consumed — should be 100 - 4 = 96 (re-synced, not compounded).
    $component->set('items.0.qty', 4)->call('save')->assertHasNoErrors();
    expect(StockLedger::currentQty($spare->id))->toBe(96.0);
});

it('does not deduct labour lines from stock', function () {
    $cn = Consumable::factory()->create();

    Livewire::test(Edit::class, ['consumable' => $cn])
        ->set('items', [
            ['id' => null, 'line_type' => 'labour', 'spare_id' => null, 'labour_id' => null, 'uom_id' => null, 'tax_id' => null, 'description' => 'labour loss', 'hsn_code' => null, 'qty' => 3, 'unit_rate' => 100, 'tax_percent' => 0, 'sequence_no' => 1],
        ])
        ->call('save')
        ->assertHasNoErrors();

    expect(StockEntry::where('source_type', Consumable::class)->where('source_id', $cn->id)->count())->toBe(0);
});

it('deletes a consumable from the index', function () {
    $cn = Consumable::factory()->create();

    Livewire::test(Index::class)->call('delete', $cn->id);

    expect(Consumable::find($cn->id))->toBeNull();
});
