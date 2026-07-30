<?php

use App\Models\User;
use App\Modules\ServicePackageMaster\Livewire\Edit;
use App\Modules\ServicePackageMaster\Livewire\Index;
use App\Modules\ServicePackageMaster\Models\ServicePackageMaster;
use App\Modules\ServicePackageMaster\Models\ServicePackageService;
use App\Modules\ServicePackageMaster\Models\ServicePackageSpare;
use App\Modules\ServiceTypeMaster\Models\ServiceTypeMaster;
use App\Modules\SpareMaster\Models\SpareMaster;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(adminUser());
});

it('renders the index page', function () {
    ServicePackageMaster::factory()->count(3)->create();

    $this->get(route('service-package-master.index'))
        ->assertOk()
        ->assertSeeLivewire(Index::class);
});

it('filters by AMC vs Combo kind', function () {
    ServicePackageMaster::factory()->create(['name' => 'COMBO PACK XYZ', 'is_amc' => false]);
    ServicePackageMaster::factory()->amc()->create(['name' => 'AMC PACK QQQ']);

    Livewire::test(Index::class)
        ->set('kindFilter', 'amc')
        ->assertSee('AMC PACK QQQ')
        ->assertDontSee('COMBO PACK XYZ')
        ->set('kindFilter', 'combo')
        ->assertSee('COMBO PACK XYZ')
        ->assertDontSee('AMC PACK QQQ');
});

it('creates a package with capital typing and no line items', function () {
    Livewire::test(Edit::class)
        ->set('name', 'standard amc')
        ->set('code', 'sp-amc1')
        ->set('description', 'one year cover')
        ->set('is_amc', true)
        ->set('validity_months', 12)
        ->set('total_price', 5000)
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect(route('service-package-master.index'));

    $row = ServicePackageMaster::first();
    expect($row->name)->toBe('STANDARD AMC')
        ->and($row->code)->toBe('SP-AMC1')
        ->and($row->is_amc)->toBeTrue()
        ->and($row->validity_months)->toBe(12);
});

it('persists line items when added', function () {
    $a = ServiceTypeMaster::factory()->create(['name' => 'BASIC SERVICE']);
    $b = ServiceTypeMaster::factory()->create(['name' => 'MAJOR SERVICE']);

    Livewire::test(Edit::class)
        ->set('name', 'AMC GOLD')
        ->call('addService')
        ->call('addService')
        ->set('services.0.service_type_id', $a->id)
        ->set('services.0.due_after_months', 3)
        ->set('services.1.service_type_id', $b->id)
        ->set('services.1.due_after_months', 6)
        ->set('services.1.due_after_km', 10000)
        ->call('save')
        ->assertHasNoErrors();

    $package = ServicePackageMaster::with('services')->first();
    expect($package->services)->toHaveCount(2)
        ->and($package->services[0]->service_type_id)->toBe($a->id)
        ->and($package->services[0]->sequence_no)->toBe(1)
        ->and($package->services[1]->service_type_id)->toBe($b->id)
        ->and($package->services[1]->due_after_km)->toBe(10000);
});

it('strips blank line-item rows before validating', function () {
    $a = ServiceTypeMaster::factory()->create(['name' => 'BASIC SERVICE']);

    Livewire::test(Edit::class)
        ->set('name', 'AMC SILVER')
        ->call('addService')
        ->call('addService')                            // blank trailing row
        ->set('services.0.service_type_id', $a->id)
        ->call('save')
        ->assertHasNoErrors();

    expect(ServicePackageMaster::first()->services)->toHaveCount(1);
});

it('removes a line item', function () {
    $a = ServiceTypeMaster::factory()->create();
    $b = ServiceTypeMaster::factory()->create();

    Livewire::test(Edit::class)
        ->call('addService')
        ->call('addService')
        ->set('services.0.service_type_id', $a->id)
        ->set('services.1.service_type_id', $b->id)
        ->call('removeService', 0)
        ->assertSet('services.0.service_type_id', $b->id)
        ->assertCount('services', 1);
});

it('updates an existing package and syncs line items', function () {
    $package = ServicePackageMaster::factory()->create(['name' => 'OLD']);
    $a = ServiceTypeMaster::factory()->create();
    $b = ServiceTypeMaster::factory()->create();
    $package->services()->createMany([
        ['service_type_id' => $a->id, 'sequence_no' => 1],
        ['service_type_id' => $b->id, 'sequence_no' => 2],
    ]);

    Livewire::test(Edit::class, ['servicePackageMaster' => $package])
        ->set('name', 'updated')
        ->call('removeService', 1)                      // drop second line
        ->call('save')
        ->assertHasNoErrors();

    $fresh = $package->fresh()->load('services');
    expect($fresh->name)->toBe('UPDATED')
        ->and($fresh->services)->toHaveCount(1)
        ->and($fresh->services[0]->service_type_id)->toBe($a->id);
});

it('validates required name on save', function () {
    Livewire::test(Edit::class)
        ->set('name', '')
        ->call('save')
        ->assertHasErrors(['name' => 'required']);
});

it('enforces unique code', function () {
    ServicePackageMaster::factory()->create(['code' => 'DUP-PKG']);

    Livewire::test(Edit::class)
        ->set('name', 'NEW')
        ->set('code', 'DUP-PKG')
        ->call('save')
        ->assertHasErrors(['code']);
});

it('deletes a package from the index', function () {
    $row = ServicePackageMaster::factory()->create();

    Livewire::test(Index::class)
        ->call('delete', $row->id);

    expect(ServicePackageMaster::find($row->id))->toBeNull();
});

it('deleting a package cascades its line items', function () {
    $package = ServicePackageMaster::factory()->create();
    $st = ServiceTypeMaster::factory()->create();
    $package->services()->create(['service_type_id' => $st->id, 'sequence_no' => 1]);

    Livewire::test(Index::class)
        ->call('delete', $package->id);

    expect(ServicePackageService::where('service_package_id', $package->id)->count())->toBe(0);
});

it('persists included spares with pricing and a usage rule', function () {
    $spare = SpareMaster::factory()->create(['name' => 'OIL FILTER']);

    Livewire::test(Edit::class)
        ->set('name', 'gold combo')
        ->set('usage_rule', 'one_time_use')
        ->set('net_price', 8000)
        ->set('offer_price', 6500)
        ->set('saving_price', 1500)
        ->call('addSpare')
        ->set('spares.0.spare_id', $spare->id)
        ->set('spares.0.description', 'engine oil filter')
        ->set('spares.0.rate', 450)
        ->set('spares.0.quantity', 1)
        ->set('spares.0.tax_percent', 18)
        ->set('spares.0.offer_price', 400)
        ->call('save')
        ->assertHasNoErrors();

    $package = ServicePackageMaster::with('spares')->first();
    expect($package->usage_rule)->toBe('one_time_use')
        ->and((float) $package->offer_price)->toBe(6500.0)
        ->and($package->spares)->toHaveCount(1)
        ->and($package->spares[0]->description)->toBe('ENGINE OIL FILTER')
        ->and((float) $package->spares[0]->tax_percent)->toBe(18.0);
});

it('auto-fills a spare line from the picked spare', function () {
    $spare = SpareMaster::factory()->create(['name' => 'BRAKE PAD']);

    Livewire::test(Edit::class)
        ->call('addSpare')
        ->set('spares.0.spare_id', $spare->id)
        ->assertSet('spares.0.description', 'BRAKE PAD');
});

it('strips blank spare rows before validating', function () {
    Livewire::test(Edit::class)
        ->set('name', 'combo silver')
        ->call('addSpare')                              // blank row, no spare/description
        ->call('save')
        ->assertHasNoErrors();

    expect(ServicePackageMaster::first()->spares)->toHaveCount(0);
});

it('cascades spares on package delete', function () {
    $package = ServicePackageMaster::factory()->create();
    $package->spares()->create(['description' => 'X', 'sequence_no' => 1]);

    Livewire::test(Index::class)->call('delete', $package->id);

    expect(ServicePackageSpare::where('service_package_id', $package->id)->count())->toBe(0);
});

it('requires authentication', function () {
    auth()->logout();

    $this->get(route('service-package-master.index'))->assertRedirect(route('login'));
});

it('renders the create page', function () {
    $this->get(route('service-package-master.create'))
        ->assertOk()
        ->assertSeeLivewire(Edit::class);
});

it('blocks the create page for a user without create permission', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('service_package_master.view');
    $this->actingAs($user);

    $this->get(route('service-package-master.create'))->assertForbidden();
});
