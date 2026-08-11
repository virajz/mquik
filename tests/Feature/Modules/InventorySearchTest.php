<?php

use App\Modules\Inventory\Models\StockEntry;
use App\Modules\Inventory\Services\StockIssuer;
use App\Modules\InventoryGroupMaster\Models\InventoryGroupMaster;
use App\Modules\InventorySearch\Livewire\Index;
use App\Modules\PartTypeMaster\Models\PartTypeMaster;
use App\Modules\SpareBrandMaster\Models\SpareBrandMaster;
use App\Modules\SpareMaster\Models\SpareMaster;
use App\Modules\VehicleVariantMaster\Models\VehicleVariantMaster;
use App\Modules\VendorMaster\Models\VendorMaster;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(adminUser());
});

it('renders the search page', function () {
    SpareMaster::factory()->create();

    $this->get(route('inventory-search.index'))
        ->assertOk()
        ->assertSeeLivewire(Index::class);
});

it('requires authentication', function () {
    auth()->logout();
    $this->get(route('inventory-search.index'))->assertRedirect(route('login'));
});

it('filters by part type', function () {
    $pt = PartTypeMaster::factory()->create();
    SpareMaster::factory()->create(['part_type_id' => $pt->id, 'name' => 'GENUINE PAD']);
    SpareMaster::factory()->create(['name' => 'OTHER PAD']);

    Livewire::test(Index::class)
        ->set('partTypeFilter', (string) $pt->id)
        ->assertSee('GENUINE PAD')
        ->assertDontSee('OTHER PAD');
});

it('filters by vendor via the parts brand', function () {
    // Suppliers are tracked against the parts brand, not each part number.
    $vendor = VendorMaster::factory()->create();
    $brand = SpareBrandMaster::factory()->create();
    $brand->vendors()->attach($vendor->id);

    SpareMaster::factory()->create(['name' => 'VENDOR PART', 'spare_brand_id' => $brand->id]);
    SpareMaster::factory()->create(['name' => 'NO VENDOR PART']);

    Livewire::test(Index::class)
        ->set('vendorFilter', (string) $vendor->id)
        ->assertSee('VENDOR PART')
        ->assertDontSee('NO VENDOR PART');
});

it('lists alternatives in the same sub-group sharing a vehicle variant', function () {
    $subGroup = InventoryGroupMaster::factory()->create(['name' => 'BRAKE PADS', 'parent_id' => null]);
    $variant = VehicleVariantMaster::factory()->create();

    $primary = SpareMaster::factory()->create(['name' => 'PAD PRIMARY', 'inventory_sub_group_id' => $subGroup->id]);
    $alt = SpareMaster::factory()->create(['name' => 'PAD ALTERNATIVE', 'inventory_sub_group_id' => $subGroup->id]);
    $unrelated = SpareMaster::factory()->create(['name' => 'PAD UNRELATED']);

    $primary->vehicleVariants()->attach($variant->id);
    $alt->vehicleVariants()->attach($variant->id);

    $component = Livewire::test(Index::class)
        ->call('showAlternatives', $primary->id)
        ->assertSet('alternativesForId', $primary->id);

    $names = collect($component->instance()->alternativeRows)->pluck('name');
    expect($names)->toContain('PAD ALTERNATIVE')
        ->not->toContain('PAD UNRELATED')
        ->not->toContain('PAD PRIMARY');
});

it('hides parts with nothing on the shelf when in-stock-only is on', function () {
    $onShelf = SpareMaster::factory()->create(['name' => 'BRAKE PAD IN STOCK']);
    $none = SpareMaster::factory()->create(['name' => 'BRAKE PAD NO STOCK']);
    $drained = SpareMaster::factory()->create(['name' => 'BRAKE PAD DRAINED']);

    StockIssuer::receive($onShelf->id, 5, 100, StockEntry::TYPE_OPENING);
    // Received then fully issued — nets to zero, so it must hide too.
    StockIssuer::receive($drained->id, 4, 100, StockEntry::TYPE_OPENING);
    StockIssuer::issue($drained->id, 4, StockEntry::TYPE_CONSUMPTION);

    Livewire::test(Index::class)
        ->assertSee('BRAKE PAD NO STOCK')
        ->set('inStockOnly', true)
        ->assertSee('BRAKE PAD IN STOCK')
        ->assertDontSee('BRAKE PAD NO STOCK')
        ->assertDontSee('BRAKE PAD DRAINED');
});

it('keeps a negative balance visible — it still needs attention', function () {
    $negative = SpareMaster::factory()->create(['name' => 'OVERSOLD PART']);
    StockIssuer::receive($negative->id, 2, 100, StockEntry::TYPE_OPENING);
    StockIssuer::issue($negative->id, 5, StockEntry::TYPE_CONSUMPTION, null, ['allow_negative' => true]);

    Livewire::test(Index::class)
        ->set('inStockOnly', true)
        ->assertSee('OVERSOLD PART');
});

it('clears the in-stock filter along with the rest', function () {
    Livewire::test(Index::class)
        ->set('inStockOnly', true)
        ->call('clearFilters')
        ->assertSet('inStockOnly', false);
});
