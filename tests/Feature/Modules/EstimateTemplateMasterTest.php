<?php

use App\Modules\EstimateTemplateMaster\Livewire\Edit;
use App\Modules\EstimateTemplateMaster\Livewire\Index;
use App\Modules\EstimateTemplateMaster\Models\EstimateTemplateMaster;
use App\Modules\SpareMaster\Models\SpareMaster;
use App\Modules\TaxMaster\Models\TaxMaster;
use App\Modules\VehicleBrandMaster\Models\VehicleBrandMaster;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(adminUser());
});

it('renders the index page', function () {
    EstimateTemplateMaster::factory()->count(2)->create();

    $this->get(route('estimate-template-master.index'))
        ->assertOk()
        ->assertSeeLivewire(Index::class);
});

it('requires authentication', function () {
    auth()->logout();
    $this->get(route('estimate-template-master.index'))->assertRedirect(route('login'));
});

it('creates a template with spare lines', function () {
    $spare = SpareMaster::factory()->create();

    Livewire::test(Edit::class)
        ->set('name', 'pms basic')
        ->call('addItem', 'spare')
        ->set('items.0.spare_id', $spare->id)
        ->set('items.0.default_qty', 2)
        ->call('save')
        ->assertHasNoErrors();

    $template = EstimateTemplateMaster::with('items')->firstOrFail();
    expect($template->name)->toBe('PMS BASIC')
        ->and($template->items)->toHaveCount(1)
        ->and($template->items[0]->line_type)->toBe('spare')
        ->and($template->items[0]->spare_id)->toBe($spare->id);
});

it('deletes a template from the index', function () {
    $t = EstimateTemplateMaster::factory()->create();

    Livewire::test(Index::class)->call('delete', $t->id);

    expect(EstimateTemplateMaster::find($t->id))->toBeNull();
});

it('saves the row-19 header fields and priced line', function () {
    $brand = VehicleBrandMaster::factory()->create();
    $tax = TaxMaster::factory()->create(['gst_percent' => 18]);
    $spare = SpareMaster::factory()->create();

    Livewire::test(Edit::class)
        ->set('name', 'brake combo')
        ->set('category', 'brake')
        ->set('effective_date', '2026-07-01')
        ->set('vehicle_brand_id', $brand->id)
        ->call('addItem', 'spare')
        ->set('items.0.spare_id', $spare->id)
        ->set('items.0.default_qty', 2)
        ->set('items.0.unit_rate', 100)
        ->set('items.0.tax_id', $tax->id)
        ->call('save')
        ->assertHasNoErrors();

    $t = EstimateTemplateMaster::with('items')->firstOrFail();
    expect($t->category)->toBe('brake')
        ->and($t->effective_date->format('Y-m-d'))->toBe('2026-07-01')
        ->and($t->vehicle_brand_id)->toBe($brand->id);

    $item = $t->items->first();
    expect((float) $item->unit_rate)->toBe(100.0)
        ->and($item->lineTotal())->toBe(200.0)
        ->and($item->taxAmount())->toBe(36.0)
        ->and($item->netAmount())->toBe(236.0);
});

it('uploads and clears a template brochure', function () {
    Storage::fake('public');

    $component = Livewire::test(Edit::class)
        ->set('name', 'with brochure')
        ->set('brochureFile', UploadedFile::fake()->create('brochure.pdf', 50, 'application/pdf'))
        ->call('save')
        ->assertHasNoErrors();

    $t = EstimateTemplateMaster::firstOrFail();
    expect($t->brochure_path)->not->toBeNull();
    Storage::disk('public')->assertExists($t->brochure_path);

    Livewire::test(Edit::class, ['estimateTemplate' => $t])
        ->call('clearBrochure')
        ->call('save')
        ->assertHasNoErrors();

    expect($t->fresh()->brochure_path)->toBeNull();
});

it('validates category against the allowed list', function () {
    Livewire::test(Edit::class)
        ->set('name', 'bad cat')
        ->set('category', 'nonsense')
        ->call('save')
        ->assertHasErrors(['category']);
});
