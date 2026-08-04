<?php

use App\Modules\HsnMaster\Models\HsnMaster;
use App\Modules\SpareBrandMaster\Models\SpareBrandMaster;
use App\Modules\SpareMaster\Livewire\Edit;
use App\Modules\SpareMaster\Livewire\Index;
use App\Modules\SpareMaster\Models\SpareMaster;
use App\Modules\SpareMaster\Models\SpareRateHistory;
use App\Modules\TaxMaster\Models\TaxMaster;
use App\Modules\VehicleBrandMaster\Models\VehicleBrandMaster;
use App\Modules\VehicleModelMaster\Models\VehicleModelMaster;
use App\Modules\VehicleVariantMaster\Models\VehicleVariantMaster;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
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
    $hsn8708 = HsnMaster::factory()->create(['code' => '8708']);
    $hsn8421 = HsnMaster::factory()->create(['code' => '8421']);
    SpareMaster::factory()->create(['name' => 'BREMBO BRAKE PAD', 'spare_code' => 'BBP-001', 'hsn_id' => $hsn8708->id]);
    SpareMaster::factory()->create(['name' => 'BOSCH AIR FILTER', 'spare_code' => 'BAF-001', 'hsn_id' => $hsn8421->id]);

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
    SpareMaster::factory()->create(['name' => 'GENERAL ITEM XYZ', 'spare_type' => SpareMaster::TYPE_VEHICLE_SPECIFIC]);
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

it('requires tyre dimension and rim size when the type is Tyre', function () {
    Livewire::test(Edit::class)
        ->set('name', 'A TYRE')
        ->set('spare_type', SpareMaster::TYPE_TYRE)
        ->call('save')
        ->assertHasErrors(['tyre_dimension', 'rim_size']);
});

it('clears tyre fields when the type moves away from Tyre', function () {
    Livewire::test(Edit::class)
        ->set('name', 'TYRE')
        ->set('spare_type', SpareMaster::TYPE_TYRE)
        ->set('tyre_dimension', '195/65 R15')
        ->set('rim_size', '15')
        ->set('spare_type', SpareMaster::TYPE_COMMON)
        ->assertSet('tyre_dimension', null)
        ->assertSet('rim_size', null);
});

it('drops the vehicle compatibility list when the type is not vehicle-specific', function () {
    $variant = VehicleVariantMaster::factory()->create();

    $component = Livewire::test(Edit::class)
        ->set('name', 'UNIVERSAL OIL')
        ->set('spare_type', SpareMaster::TYPE_VEHICLE_SPECIFIC)
        ->set('variant_ids', [$variant->id]);

    expect($component->instance()->needsVehicleCompatibility())->toBeTrue();

    $component->set('spare_type', SpareMaster::TYPE_COMMON)
        ->assertSet('variant_ids', []);

    expect($component->instance()->needsVehicleCompatibility())->toBeFalse();
});

it('keeps compatibility available only for vehicle-specific parts', function () {
    foreach ([
        SpareMaster::TYPE_VEHICLE_SPECIFIC => true,
        SpareMaster::TYPE_TYRE => false,
        SpareMaster::TYPE_COMMON => false,
    ] as $type => $expected) {
        $component = Livewire::test(Edit::class)->set('spare_type', $type);
        expect($component->instance()->needsVehicleCompatibility())->toBe($expected);
    }
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

it('derives rate before tax when MRP is entered', function () {
    $tax = TaxMaster::factory()->create(['gst_percent' => 18, 'cess_percent' => 0]);

    Livewire::test(Edit::class)
        ->set('tax_id', $tax->id)
        ->set('mrp', 118)
        ->assertSet('rate_before_tax', 100.0);
});

it('derives MRP when rate before tax is entered', function () {
    $tax = TaxMaster::factory()->create(['gst_percent' => 18, 'cess_percent' => 0]);

    Livewire::test(Edit::class)
        ->set('tax_id', $tax->id)
        ->set('rate_before_tax', 100)
        ->assertSet('mrp', 118.0);
});

it('re-derives MRP from the rate when the tax slab changes', function () {
    $gst18 = TaxMaster::factory()->create(['gst_percent' => 18, 'cess_percent' => 0]);
    $gst28 = TaxMaster::factory()->create(['gst_percent' => 28, 'cess_percent' => 0]);

    Livewire::test(Edit::class)
        ->set('tax_id', $gst18->id)
        ->set('rate_before_tax', 100)
        ->assertSet('mrp', 118.0)
        ->set('tax_id', $gst28->id)
        ->assertSet('mrp', 128.0);
});

it('includes cess in the MRP linkage', function () {
    $tax = TaxMaster::factory()->create(['gst_percent' => 18, 'cess_percent' => 2]);

    Livewire::test(Edit::class)
        ->set('tax_id', $tax->id)
        ->set('mrp', 120)
        ->assertSet('rate_before_tax', 100.0);
});

it('persists both MRP and rate', function () {
    $tax = TaxMaster::factory()->create(['gst_percent' => 18, 'cess_percent' => 0]);

    Livewire::test(Edit::class)
        ->set('name', 'brake pad front')
        ->set('tax_id', $tax->id)
        ->set('mrp', 118)
        ->call('save')
        ->assertHasNoErrors();

    $spare = SpareMaster::where('name', 'BRAKE PAD FRONT')->firstOrFail();
    expect((float) $spare->mrp)->toBe(118.0)
        ->and((float) $spare->rate_before_tax)->toBe(100.0);
});

it('derives the rate when MRP is typed BEFORE the tax slab is picked', function () {
    // The real advisor flow: type the printed price, then choose the slab.
    $tax = TaxMaster::factory()->create(['gst_percent' => 18, 'cess_percent' => 0]);

    Livewire::test(Edit::class)
        ->set('mrp', 118)
        ->set('tax_id', $tax->id)
        ->assertSet('mrp', 118.0)          // the typed figure must survive
        ->assertSet('rate_before_tax', 100.0);
});

it('keeps the rate authoritative when the rate was typed before the slab', function () {
    $tax = TaxMaster::factory()->create(['gst_percent' => 18, 'cess_percent' => 0]);

    Livewire::test(Edit::class)
        ->set('rate_before_tax', 100)
        ->set('tax_id', $tax->id)
        ->assertSet('rate_before_tax', 100.0)
        ->assertSet('mrp', 118.0);
});

it('switching slabs after typing MRP re-derives the rate, not the MRP', function () {
    $gst18 = TaxMaster::factory()->create(['gst_percent' => 18, 'cess_percent' => 0]);
    $gst28 = TaxMaster::factory()->create(['gst_percent' => 28, 'cess_percent' => 0]);

    Livewire::test(Edit::class)
        ->set('mrp', 118)
        ->set('tax_id', $gst18->id)
        ->assertSet('rate_before_tax', 100.0)
        ->set('tax_id', $gst28->id)
        ->assertSet('mrp', 118.0)           // still what the user typed
        ->assertSet('rate_before_tax', 92.19);
});

it('persists the inventory type', function () {
    Livewire::test(Edit::class)
        ->set('name', 'ENGINE OIL')
        ->set('inventory_type', 'lubricants')
        ->call('save')
        ->assertHasNoErrors();

    expect(SpareMaster::first()->inventory_type)->toBe('lubricants');
});

it('rejects an unknown inventory type', function () {
    Livewire::test(Edit::class)
        ->set('name', 'BAD TYPE')
        ->set('inventory_type', 'not_a_type')
        ->call('save')
        ->assertHasErrors(['inventory_type']);
});

it('stores a spare image / application guide attachment', function () {
    Storage::fake('public');

    Livewire::test(Edit::class)
        ->set('name', 'BRAKE DISC')
        ->call('addAttachment')
        ->set('attachments.0.attachment_type', 'spare_image')
        ->set('attachmentFiles.0', UploadedFile::fake()->image('disc.jpg'))
        ->call('save')
        ->assertHasNoErrors();

    $spare = SpareMaster::first();
    expect($spare->attachments)->toHaveCount(1)
        ->and($spare->attachments->first()->attachment_type)->toBe('spare_image')
        ->and($spare->attachments->first()->kind)->toBe('image');
    Storage::disk('public')->assertExists($spare->attachments->first()->path);
});

it('records a dated rate revision when the rate changes', function () {
    $spare = SpareMaster::factory()->create(['name' => 'BRAKE PAD', 'rate_before_tax' => 1000]);

    Livewire::test(Edit::class, ['spare' => $spare])
        ->set('rate_before_tax', 1250)
        ->call('save')
        ->assertHasNoErrors();

    $revision = $spare->fresh()->rateHistory->first();
    expect((float) $revision->rate_before_tax)->toBe(1250.0)
        ->and($revision->source)->toBe(SpareRateHistory::SOURCE_MANUAL)
        ->and($revision->effective_from->isToday())->toBeTrue();

    // Saving again without touching the rate must not pile up duplicates.
    Livewire::test(Edit::class, ['spare' => $spare->fresh()])
        ->set('description', 'FRONT AXLE')
        ->call('save')
        ->assertHasNoErrors();

    expect($spare->fresh()->rateHistory)->toHaveCount(1);
});

it('shows the rate history newest-first on the edit page', function () {
    $spare = SpareMaster::factory()->create(['rate_before_tax' => 1773.44]);
    $spare->rateHistory()->createMany([
        ['rate_before_tax' => 1540.62, 'mrp' => 1817.93, 'effective_from' => '2021-02-01'],
        ['rate_before_tax' => 1773.44, 'mrp' => 2092.66, 'effective_from' => '2025-07-07'],
    ]);

    Livewire::test(Edit::class, ['spare' => $spare])
        ->assertSee('Rate History')
        ->assertSeeInOrder(['07 Jul 2025', '01 Feb 2021'])
        // Change vs. the previous revision; the oldest row has nothing to compare to.
        ->assertSee('+15.1%');
});

it('hides the rate history section for a spare that has none', function () {
    Livewire::test(Edit::class, ['spare' => SpareMaster::factory()->create()])
        ->assertDontSee('Rate History');
});
