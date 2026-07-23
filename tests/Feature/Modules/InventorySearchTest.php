<?php

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
