<?php

use App\Modules\SpareBrandMaster\Models\SpareBrandMaster;
use App\Modules\SpareMaster\Livewire\Edit;
use App\Modules\SpareMaster\Livewire\Index;
use App\Modules\SpareMaster\Models\SpareMaster;
use App\Modules\TaxMaster\Models\TaxMaster;
use App\Modules\VehicleBrandMaster\Models\VehicleBrandMaster;
use App\Modules\VehicleModelMaster\Models\VehicleModelMaster;
use App\Modules\VehicleVariantMaster\Models\VehicleVariantMaster;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(adminUser());
});

it('renders the index page', function () {
    SpareMaster::factory()->count(3)->create();

    $this->get(route('spare-master.index'))
        ->assertOk()
        ->assertSeeLivewire(Index::class);
});

it('filters records by search across name / part no / hsn', function () {
    SpareMaster::factory()->create(['name' => 'BREMBO BRAKE PAD', 'spare_code' => 'BBP-001', 'hsn_code' => '8708']);
    SpareMaster::factory()->create(['name' => 'BOSCH AIR FILTER', 'spare_code' => 'BAF-001', 'hsn_code' => '8421']);

    Livewire::test(Index::class)
        ->set('search', 'BREMBO')
        ->assertSee('BREMBO BRAKE PAD')
        ->assertDontSee('BOSCH AIR FILTER')
        ->set('search', 'BAF-001')
        ->assertSee('BOSCH AIR FILTER')
        ->assertDontSee('BREMBO BRAKE PAD')
        ->set('search', '8708')
        ->assertSee('BREMBO BRAKE PAD')
        ->assertDontSee('BOSCH AIR FILTER');
});

it('filters by tyre vs general category', function () {
    SpareMaster::factory()->create(['name' => 'GENERAL ITEM XYZ', 'is_tyre' => false]);
    SpareMaster::factory()->tyre()->create(['name' => 'MRF TYRE QQQ']);

    Livewire::test(Index::class)
        ->set('categoryFilter', 'tyre')
        ->assertSee('MRF TYRE QQQ')
        ->assertDontSee('GENERAL ITEM XYZ')
        ->set('categoryFilter', 'general')
        ->assertSee('GENERAL ITEM XYZ')
        ->assertDontSee('MRF TYRE QQQ');
});

it('filters by brand', function () {
    $a = SpareBrandMaster::factory()->create(['name' => 'BRAND ALPHA']);
    $b = SpareBrandMaster::factory()->create(['name' => 'BRAND BETA']);
    SpareMaster::factory()->create(['name' => 'PART AAA', 'spare_brand_id' => $a->id]);
    SpareMaster::factory()->create(['name' => 'PART BBB', 'spare_brand_id' => $b->id]);

    Livewire::test(Index::class)
        ->set('brandFilter', (string) $a->id)
        ->assertSee('PART AAA')
        ->assertDontSee('PART BBB');
});

it('creates a spare via the page-based edit form with capital typing', function () {
    Livewire::test(Edit::class)
        ->set('name', 'new brake pad')
        ->set('spare_code', 'sp-new1')
        ->set('description', 'fits maruti swift')
        ->set('rate_before_tax', 250.00)
        ->call('save')
        ->assertHasNoErrors();

    $row = SpareMaster::first();
    expect($row->name)->toBe('NEW BRAKE PAD')
        ->and($row->spare_code)->toBe('SP-NEW1')
        ->and($row->description)->toBe('FITS MARUTI SWIFT');
});

it('updates an existing spare via the edit page', function () {
    $spare = SpareMaster::factory()->create(['name' => 'OLD NAME']);

    Livewire::test(Edit::class, ['spare' => $spare])
        ->set('name', 'updated name')
        ->call('save')
        ->assertHasNoErrors();

    expect($spare->fresh()->name)->toBe('UPDATED NAME');
});

it('requires tyre dimension and rim size when is_tyre is true', function () {
    Livewire::test(Edit::class)
        ->set('name', 'A TYRE')
        ->set('is_tyre', true)
        ->call('save')
        ->assertHasErrors(['tyre_dimension', 'rim_size']);
});

it('clears tyre fields when is_tyre is toggled off', function () {
    Livewire::test(Edit::class)
        ->set('name', 'TYRE')
        ->set('is_tyre', true)
        ->set('tyre_dimension', '195/65 R15')
        ->set('rim_size', '15')
        ->set('is_tyre', false)
        ->assertSet('tyre_dimension', null)
        ->assertSet('rim_size', null);
});

it('rate including tax is computed live from rate_before_tax + tax slab', function () {
    $tax = TaxMaster::factory()->create(['name' => 'GST 18%', 'gst_percent' => 18, 'cess_percent' => 0]);

    $component = Livewire::test(Edit::class)
        ->set('rate_before_tax', 100.00)
        ->set('tax_id', $tax->id);

    expect($component->get('rateInclTax'))->toBe(118.0);
});

it('syncs vehicle variant compatibility', function () {
    $brand = VehicleBrandMaster::factory()->create(['name' => 'MARUTI']);
    $model = VehicleModelMaster::factory()->create(['name' => 'SWIFT', 'brand_id' => $brand->id]);
    $variant1 = VehicleVariantMaster::factory()->create(['name' => 'VXI', 'model_id' => $model->id]);
    $variant2 = VehicleVariantMaster::factory()->create(['name' => 'ZXI', 'model_id' => $model->id]);

    Livewire::test(Edit::class)
        ->set('name', 'BRAKE PAD')
        ->set('variant_ids', [$variant1->id, $variant2->id])
        ->call('save')
        ->assertHasNoErrors();

    $spare = SpareMaster::first();
    expect($spare->vehicleVariants->pluck('id')->all())->toEqualCanonicalizing([$variant1->id, $variant2->id]);
});

it('deletes a spare from the index', function () {
    $spare = SpareMaster::factory()->create();

    Livewire::test(Index::class)
        ->call('delete', $spare->id);

    expect(SpareMaster::find($spare->id))->toBeNull();
});

it('validates required name on save', function () {
    Livewire::test(Edit::class)
        ->set('name', '')
        ->call('save')
        ->assertHasErrors(['name' => 'required']);
});

it('enforces unique spare_code', function () {
    SpareMaster::factory()->create(['spare_code' => 'DUP-001']);

    Livewire::test(Edit::class)
        ->set('name', 'NEW')
        ->set('spare_code', 'DUP-001')
        ->call('save')
        ->assertHasErrors(['spare_code']);
});

it('requires authentication', function () {
    auth()->logout();

    $this->get(route('spare-master.index'))->assertRedirect(route('login'));
});
