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
use App\Modules\WorkshopDepartmentMaster\Models\WorkshopDepartmentMaster;
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

it('matches multi-word searches across name and description together', function () {
    // The legacy import puts the applicable vehicles in the description.
    SpareMaster::factory()->create(['name' => 'ABSORBER SHOCK RR', 'description' => 'PASSAT, JETTA, LAURA, SUPERB, YETI']);
    SpareMaster::factory()->create(['name' => 'ABSORBER SHOCK FR', 'description' => 'OCTAVIA, RAPID']);
    SpareMaster::factory()->create(['name' => 'BRAKE PAD SET', 'description' => 'LAURA']);

    Livewire::test(Index::class)
        ->set('search', 'absorber laura')
        ->assertSee('ABSORBER SHOCK RR')
        ->assertDontSee('ABSORBER SHOCK FR')
        ->assertDontSee('BRAKE PAD SET');
});

it('requires every search token to match somewhere', function () {
    SpareMaster::factory()->create(['name' => 'ABSORBER SHOCK RR', 'description' => 'LAURA']);

    Livewire::test(Index::class)
        ->set('search', 'absorber laura')
        ->assertSee('ABSORBER SHOCK RR')
        // "yeti" appears in no field, so the row drops out.
        ->set('search', 'absorber laura yeti')
        ->assertDontSee('ABSORBER SHOCK RR');
});

it('still finds a spare by part no and by HSN code', function () {
    $hsn = HsnMaster::factory()->create(['code' => '87088000']);
    SpareMaster::factory()->create(['name' => 'ABSORBER SHOCK RR', 'spare_code' => '1K0513029HR', 'hsn_id' => $hsn->id]);

    Livewire::test(Index::class)
        ->set('search', '1K0513029')
        ->assertSee('ABSORBER SHOCK RR')
        ->set('search', '87088000')
        ->assertSee('ABSORBER SHOCK RR');
});

it('filters spares by the vehicle model they fit, and by exact variant', function () {
    $brand = VehicleBrandMaster::factory()->create(['name' => 'SKODA']);
    $model = VehicleModelMaster::factory()->create(['name' => 'LAURA', 'brand_id' => $brand->id]);
    $other = VehicleModelMaster::factory()->create(['name' => 'OCTAVIA', 'brand_id' => $brand->id]);
    $vxi = VehicleVariantMaster::factory()->create(['name' => 'AMBITION', 'model_id' => $model->id]);
    $zxi = VehicleVariantMaster::factory()->create(['name' => 'ELEGANCE', 'model_id' => $model->id]);
    $octaviaVariant = VehicleVariantMaster::factory()->create(['name' => 'STYLE', 'model_id' => $other->id]);

    $fitsLaura = SpareMaster::factory()->create(['name' => 'LAURA SHOCK AB']);
    $fitsLaura->vehicleVariants()->sync([$vxi->id]);
    $fitsElegance = SpareMaster::factory()->create(['name' => 'ELEGANCE ONLY PART']);
    $fitsElegance->vehicleVariants()->sync([$zxi->id]);
    $fitsOctavia = SpareMaster::factory()->create(['name' => 'OCTAVIA SHOCK AB']);
    $fitsOctavia->vehicleVariants()->sync([$octaviaVariant->id]);

    Livewire::test(Index::class)
        ->set('modelFilter', (string) $model->id)
        ->assertSee('LAURA SHOCK AB')
        ->assertSee('ELEGANCE ONLY PART')
        ->assertDontSee('OCTAVIA SHOCK AB')
        // Narrowing to one variant drops the sibling variant's part.
        ->set('variantFilter', (string) $zxi->id)
        ->assertSee('ELEGANCE ONLY PART')
        ->assertDontSee('LAURA SHOCK AB');
});

it('filters spares by workshop department', function () {
    $service = WorkshopDepartmentMaster::factory()->create(['name' => 'SERVICE']);
    $body = WorkshopDepartmentMaster::factory()->create(['name' => 'BODYSHOP']);
    SpareMaster::factory()->create(['name' => 'SERVICE PART AAA', 'workshop_department_id' => $service->id]);
    SpareMaster::factory()->create(['name' => 'BODY PART BBB', 'workshop_department_id' => $body->id]);

    Livewire::test(Index::class)
        ->set('departmentFilter', (string) $service->id)
        ->assertSee('SERVICE PART AAA')
        ->assertDontSee('BODY PART BBB');
});

it('clears every filter at once', function () {
    $dept = WorkshopDepartmentMaster::factory()->create();

    $component = Livewire::test(Index::class)
        ->set('search', 'brake')
        ->set('departmentFilter', (string) $dept->id);

    expect($component->instance()->hasActiveFilters())->toBeTrue();

    $component->call('clearFilters')
        ->assertSet('search', '')
        ->assertSet('departmentFilter', 'all');
});

it('derives expiry from the manufacturing date and the shelf life', function () {
    $spare = SpareMaster::factory()->create([
        'tracks_batch' => true, 'shelf_life_value' => 24, 'shelf_life_unit' => 'months',
    ]);

    expect($spare->expiryFor('2026-01-15'))->toBe('2028-01-15')
        ->and($spare->shelfLifeLabel())->toBe('24 Months');

    // No shelf life configured — the date has to be typed instead.
    $plain = SpareMaster::factory()->create();
    expect($plain->expiryFor('2026-01-15'))->toBeNull()
        ->and($plain->shelfLifeLabel())->toBeNull();
});

it('persists the shelf life, defaulting the unit so it is never half-filled', function () {
    // Typing a value alone now fills the unit, so the form cannot reach the
    // "value without unit" state — the rule still guards the model.
    Livewire::test(Edit::class)
        ->set('name', 'ENGINE OIL')
        ->set('shelf_life_value', 18)
        ->call('save')
        ->assertHasNoErrors();

    expect(SpareMaster::firstOrFail()->shelfLifeLabel())->toBe('18 Months');
});

it('still rejects a shelf-life value submitted with no unit', function () {
    Livewire::test(Edit::class)
        ->set('name', 'ENGINE OIL')
        ->set('shelf_life_value', 18)
        // Force the half-filled state the UI prevents, to prove the rule holds.
        ->set('shelf_life_unit', null)
        ->call('save')
        ->assertHasErrors(['shelf_life_unit']);
});

it('adds an HSN code from the spare form and selects it', function () {
    Livewire::test(Edit::class)
        ->set('hsnQuickCode', '87085000')
        ->set('hsnQuickName', 'drive axles with differential')
        ->set('hsnQuickGst', 28)
        ->call('createHsn')
        ->assertHasNoErrors();

    $hsn = HsnMaster::where('code', '87085000')->sole();
    expect($hsn->name)->toBe('DRIVE AXLES WITH DIFFERENTIAL')
        ->and((float) $hsn->gst_percent)->toBe(28.0)
        ->and($hsn->kind)->toBe(HsnMaster::KIND_HSN);
});

it('rejects a non-numeric or duplicate HSN code from the quick-add', function () {
    HsnMaster::factory()->create(['code' => '87089900']);

    Livewire::test(Edit::class)
        ->set('hsnQuickCode', 'ABCD')
        ->set('hsnQuickName', 'nope')
        ->call('createHsn')
        ->assertHasErrors(['hsnQuickCode']);

    Livewire::test(Edit::class)
        ->set('hsnQuickCode', '87089900')
        ->set('hsnQuickName', 'duplicate')
        ->call('createHsn')
        ->assertHasErrors(['hsnQuickCode']);
});

it('flags a duplicate part number while it is being typed', function () {
    SpareMaster::factory()->create(['spare_code' => 'BP-DUP']);

    Livewire::test(Edit::class)
        ->set('spare_code', 'bp-dup')
        ->assertHasErrors(['spare_code'])
        // Uppercased as typed, and clearing it clears the error.
        ->assertSet('spare_code', 'BP-DUP')
        ->set('spare_code', '')
        ->assertHasNoErrors();
});

it('bulk-selects every listed variant in the vehicle picker', function () {
    $brand = VehicleBrandMaster::factory()->create();
    $model = VehicleModelMaster::factory()->create(['brand_id' => $brand->id]);
    $variants = VehicleVariantMaster::factory()->count(4)->create(['model_id' => $model->id]);

    $component = Livewire::test(Edit::class)
        ->set('pickerBrandId', $brand->id)
        ->set('pickerModelId', $model->id)
        ->call('selectAllListedVariants');

    expect($component->get('variant_ids'))->toHaveCount(4);

    // Toggling one off, then clearing everything.
    $component->call('toggleVariant', $variants->first()->id);
    expect($component->get('variant_ids'))->toHaveCount(3);

    $component->call('clearAllVariants');
    expect($component->get('variant_ids'))->toBe([]);
});

it('renders the tick against variants that are selected', function () {
    $brand = VehicleBrandMaster::factory()->create(['name' => 'AUDI']);
    $model = VehicleModelMaster::factory()->create(['name' => 'A3', 'brand_id' => $brand->id]);
    $variants = VehicleVariantMaster::factory()->count(5)->create(['model_id' => $model->id]);

    $component = Livewire::test(Edit::class)
        ->set('pickerBrandId', $brand->id)
        ->set('pickerModelId', $model->id);

    // Nothing ticked yet — no ticked rows in the markup.
    expect(substr_count($component->html(), 'bg-lime-600'))->toBe(0);

    $component->call('selectAllListedVariants');

    // Every listed row now carries the ticked marker, and each row's key
    // encodes its state so the browser repaints it.
    $html = $component->html();
    expect(substr_count($html, 'bg-lime-600'))->toBeGreaterThanOrEqual(5)
        ->and($html)->toContain('pv-'.$variants->first()->id.'-1');

    $component->call('toggleVariant', $variants->first()->id);
    expect($component->html())->toContain('pv-'.$variants->first()->id.'-0');
});

it('selects an entire brand, and every variant of one model', function () {
    $brand = VehicleBrandMaster::factory()->create(['name' => 'AUDI']);
    $a3 = VehicleModelMaster::factory()->create(['name' => 'A3', 'brand_id' => $brand->id]);
    $a4 = VehicleModelMaster::factory()->create(['name' => 'A4', 'brand_id' => $brand->id]);
    VehicleVariantMaster::factory()->count(3)->create(['model_id' => $a3->id]);
    VehicleVariantMaster::factory()->count(2)->create(['model_id' => $a4->id]);

    $component = Livewire::test(Edit::class)->set('pickerBrandId', $brand->id);

    $component->call('toggleModel', $a3->id);
    expect($component->get('variant_ids'))->toHaveCount(3);

    $component->call('toggleBrand');
    expect($component->get('variant_ids'))->toHaveCount(5);

    // Toggling the brand again when everything is on clears it.
    $component->call('toggleBrand');
    expect($component->get('variant_ids'))->toBe([]);
});

it('shows how much of each model is selected in the brand view', function () {
    $brand = VehicleBrandMaster::factory()->create(['name' => 'AUDI']);
    $a3 = VehicleModelMaster::factory()->create(['name' => 'A3', 'brand_id' => $brand->id]);
    $variants = VehicleVariantMaster::factory()->count(4)->create(['model_id' => $a3->id]);

    $component = Livewire::test(Edit::class)
        ->set('pickerBrandId', $brand->id)
        ->call('toggleVariant', $variants->first()->id);

    $summary = collect($component->instance()->pickerModelSummary())->firstWhere('id', $a3->id);
    expect($summary['selected'])->toBe(1)
        ->and($summary['total'])->toBe(4);
});

it('summarises the selection by model instead of one chip per variant', function () {
    $brand = VehicleBrandMaster::factory()->create(['name' => 'AUDI']);
    $a3 = VehicleModelMaster::factory()->create(['name' => 'A3', 'brand_id' => $brand->id]);
    VehicleVariantMaster::factory()->count(5)->create(['model_id' => $a3->id]);

    $component = Livewire::test(Edit::class)
        ->set('pickerBrandId', $brand->id)
        ->call('toggleModel', $a3->id);

    expect($component->instance()->selectionSummary())->toBe([
        ['label' => 'AUDI A3', 'count' => 5],
    ]);
});

it('does not let the variant filter empty the model dropdown', function () {
    $brand = VehicleBrandMaster::factory()->create(['name' => 'AUDI']);
    VehicleModelMaster::factory()->create(['name' => 'A3', 'brand_id' => $brand->id]);

    $component = Livewire::test(Edit::class)
        ->set('pickerBrandId', $brand->id)
        ->set('pickerSearch', 'zzz-matches-nothing');

    // The models list is independent of the variant filter box.
    expect($component->instance()->pickerModels())->toHaveCount(1);
});

it('shows the shelf life section without needing batch tracking switched on', function () {
    // The whole block used to hide behind "Track batch & expiry", which is off
    // on all 29,681 imported spares — so nobody ever saw it.
    $html = Livewire::test(Edit::class)->assertSet('tracks_batch', false)->html();

    expect($html)->toContain('Shelf Life')
        ->and($html)->toContain('Manufacturing Date')
        ->and($html)->toContain('Expiry Date')
        // Real Flux date-pickers, not native date inputs.
        ->and($html)->not->toContain('type="date"');
});

it('stores an optional manufacturing and expiry date on the spare', function () {
    Livewire::test(Edit::class)
        ->set('name', 'ENGINE OIL 5W30')
        ->set('manufacturing_date', '2026-01-15')
        ->set('expiry_date', '2028-01-15')
        ->call('save')
        ->assertHasNoErrors();

    $spare = SpareMaster::firstOrFail();
    expect($spare->manufacturing_date->format('Y-m-d'))->toBe('2026-01-15')
        ->and($spare->expiry_date->format('Y-m-d'))->toBe('2028-01-15');
});

it('fills the expiry on the spare form as the shelf life is set', function () {
    Livewire::test(Edit::class)
        ->set('name', 'ENGINE OIL')
        ->set('manufacturing_date', '2026-01-15')
        ->set('shelf_life_value', 24)
        ->set('shelf_life_unit', 'months')
        ->assertSet('expiry_date', '2028-01-15')
        // Changing the shelf life re-derives it.
        ->set('shelf_life_unit', 'years')
        ->assertSet('expiry_date', '2050-01-15');
});

it('keeps both dates optional and rejects an expiry before manufacture', function () {
    Livewire::test(Edit::class)
        ->set('name', 'PLAIN PART')
        ->call('save')
        ->assertHasNoErrors();

    Livewire::test(Edit::class)
        ->set('name', 'BAD DATES')
        ->set('manufacturing_date', '2026-06-01')
        ->set('expiry_date', '2026-01-01')
        ->call('save')
        ->assertHasErrors(['expiry_date']);
});

it('reveals the purchase-line batch fields for a part with a shelf life, not only a batch-tracked one', function () {
    $withShelfLife = SpareMaster::factory()->create(['tracks_batch' => false, 'shelf_life_value' => 12, 'shelf_life_unit' => 'months']);
    $plain = SpareMaster::factory()->create();

    $component = Livewire::test(App\Modules\PurchaseEntry\Livewire\Edit::class);

    expect($component->instance()->needsBatchFields($withShelfLife->id))->toBeTrue()
        ->and($component->instance()->needsBatchFields($plain->id))->toBeFalse()
        ->and($component->instance()->needsBatchFields(null))->toBeFalse();
});

it('defaults the shelf-life unit to months and clears it with the value', function () {
    Livewire::test(Edit::class)
        ->set('shelf_life_value', 24)
        // Typing a number alone is enough — no half-filled "24 <blank>".
        ->assertSet('shelf_life_unit', 'months')
        ->set('shelf_life_value', null)
        ->assertSet('shelf_life_unit', null);
});

it('does not override a unit that was chosen deliberately', function () {
    Livewire::test(Edit::class)
        ->set('shelf_life_unit', 'years')
        ->set('shelf_life_value', 3)
        ->assertSet('shelf_life_unit', 'years');
});
