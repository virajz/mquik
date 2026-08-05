<?php

use App\Modules\BusinessTypeMaster\Models\BusinessTypeMaster;
use App\Modules\CustomerMaster\Models\CustomerMaster;
use App\Modules\CustomerVehicleMaster\Livewire\Edit;
use App\Modules\CustomerVehicleMaster\Livewire\Index;
use App\Modules\CustomerVehicleMaster\Models\CustomerVehicleMaster;
use App\Modules\FuelTypeMaster\Models\FuelTypeMaster;
use App\Modules\RegistrationTypeMaster\Models\RegistrationTypeMaster;
use App\Modules\VehicleBrandMaster\Models\VehicleBrandMaster;
use App\Modules\VehicleColorMaster\Models\VehicleColorMaster;
use App\Modules\VehicleModelMaster\Models\VehicleModelMaster;
use App\Modules\VehicleVariantMaster\Models\VehicleVariantMaster;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(adminUser());
});

it('renders the index page', function () {
    CustomerVehicleMaster::factory()->count(3)->create();
    $this->get(route('customer-vehicle-master.index'))->assertOk()->assertSeeLivewire(Index::class);
});

it('renders the create page', function () {
    $this->get(route('customer-vehicle-master.create'))
        ->assertOk()
        ->assertSeeLivewire(Edit::class)
        ->assertSee('New Customer Vehicle');
});

it('renders the edit page for an existing vehicle', function () {
    $vehicle = CustomerVehicleMaster::factory()->create(['registration_no' => 'GJ05XX9999']);

    $this->get(route('customer-vehicle-master.edit', $vehicle))
        ->assertOk()
        ->assertSeeLivewire(Edit::class)
        ->assertSee('GJ05XX9999');
});

it('creates a customer vehicle with all FKs and derives model_id from variant', function () {
    $customer = CustomerMaster::factory()->create();
    $variant = VehicleVariantMaster::factory()->create();
    $color = VehicleColorMaster::factory()->create();

    Livewire::test(Edit::class)
        ->set('customer_id', $customer->id)
        ->set('variant_id', $variant->id)
        ->set('color_id', $color->id)
        ->set('registration_no', 'gj 05 aa 1234')
        ->set('year_of_manufacture', 2022)
        ->set('odometer_km', 45000)
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect(route('customer-vehicle-master.index'));

    $r = CustomerVehicleMaster::firstOrFail();
    expect($r->customer_id)->toBe($customer->id)
        ->and($r->variant_id)->toBe($variant->id)
        ->and($r->model_id)->toBe($variant->model_id)
        ->and($r->color_id)->toBe($color->id)
        ->and($r->registration_no)->toBe('GJ05AA1234')
        ->and($r->year_of_manufacture)->toBe(2022)
        ->and($r->odometer_km)->toBe(45000);
});

it('requires customer_id, variant_id, and registration_no', function () {
    Livewire::test(Edit::class)
        ->call('save')
        ->assertHasErrors(['customer_id', 'variant_id', 'registration_no']);
});

it('rejects an invalid plate format', function () {
    $customer = CustomerMaster::factory()->create();
    $variant = VehicleVariantMaster::factory()->create();

    Livewire::test(Edit::class)
        ->set('customer_id', $customer->id)
        ->set('variant_id', $variant->id)
        ->set('registration_no', 'FOOBAR123')
        ->call('save')
        ->assertHasErrors(['registration_no']);
});

it('accepts BH series plates', function () {
    $customer = CustomerMaster::factory()->create();
    $variant = VehicleVariantMaster::factory()->create();

    $bh = RegistrationTypeMaster::factory()->create(['name' => 'BH SERIES']);

    Livewire::test(Edit::class)
        ->set('customer_id', $customer->id)
        ->set('variant_id', $variant->id)
        ->set('registration_type_id', $bh->id)
        ->set('registration_no', '24BH1234AA')
        ->call('save')
        ->assertHasNoErrors();

    expect(CustomerVehicleMaster::firstOrFail()->registration_no)->toBe('24BH1234AA');
});

it('rejects an invalid (non-existent) registration type', function () {
    $customer = CustomerMaster::factory()->create();
    $variant = VehicleVariantMaster::factory()->create();

    Livewire::test(Edit::class)
        ->set('customer_id', $customer->id)
        ->set('variant_id', $variant->id)
        ->set('registration_no', 'GJ05AA1234')
        ->set('registration_type_id', 999999)
        ->call('save')
        ->assertHasErrors(['registration_type_id']);
});

it('rejects duplicate registration_no', function () {
    CustomerVehicleMaster::factory()->create(['registration_no' => 'GJ05AA1234']);
    $customer = CustomerMaster::factory()->create();
    $variant = VehicleVariantMaster::factory()->create();

    Livewire::test(Edit::class)
        ->set('customer_id', $customer->id)
        ->set('variant_id', $variant->id)
        ->set('registration_no', 'GJ05AA1234')
        ->call('save')
        ->assertHasErrors(['registration_no']);
});

it('validates VIN is exactly 17 chars when provided', function () {
    $customer = CustomerMaster::factory()->create();
    $variant = VehicleVariantMaster::factory()->create();

    Livewire::test(Edit::class)
        ->set('customer_id', $customer->id)
        ->set('variant_id', $variant->id)
        ->set('registration_no', 'GJ05ZZ9999')
        ->set('vin', 'TOO-SHORT')
        ->call('save')
        ->assertHasErrors(['vin']);
});

it('searches by reg no, VIN, customer name, or phone', function () {
    $abc = CustomerMaster::factory()->create(['name' => 'RAVI ABC', 'phone' => '9999000001']);
    CustomerVehicleMaster::factory()->create(['customer_id' => $abc->id, 'registration_no' => 'GJ05AA1234']);

    $xyz = CustomerMaster::factory()->create(['name' => 'PRIYA XYZ', 'phone' => '9999000002']);
    CustomerVehicleMaster::factory()->create(['customer_id' => $xyz->id, 'registration_no' => 'MH12BB5678']);

    Livewire::test(Index::class)->set('search', 'GJ05AA')->assertSee('GJ05AA1234')->assertDontSee('MH12BB5678');
    Livewire::test(Index::class)->set('search', 'PRIYA XYZ')->assertSee('MH12BB5678')->assertDontSee('GJ05AA1234');
    Livewire::test(Index::class)->set('search', '9999000001')->assertSee('GJ05AA1234');
});

it('updates a customer vehicle', function () {
    $r = CustomerVehicleMaster::factory()->create(['registration_no' => 'GJ05OLD0000']);

    Livewire::test(Edit::class, ['customer_vehicle' => $r])
        ->set('registration_no', 'GJ05NEW9999')
        ->call('save')
        ->assertHasNoErrors();

    expect($r->fresh()->registration_no)->toBe('GJ05NEW9999');
});

it('quick-adds a new color from the form via inline create-option', function () {
    Livewire::test(Edit::class)
        ->set('colorSearch', 'midnight blue')
        ->call('createColor')
        ->assertHasNoErrors();

    $color = VehicleColorMaster::where('name', 'MIDNIGHT BLUE')->firstOrFail();

    Livewire::test(Edit::class)
        ->set('colorSearch', 'midnight blue')
        ->call('createColor')
        ->assertSet('color_id', $color->id)
        ->assertSet('colorSearch', '');
});

it('quick-add Customer wizard creates a customer and assigns it to customer_id', function () {
    $businessType = BusinessTypeMaster::firstOrCreate(
        ['name' => 'WALKING'],
        ['is_active' => true],
    );

    Livewire::test(Edit::class)
        ->set('quickCustomer.first_name', 'wizard')
        ->set('quickCustomer.phone', '9000111222')
        ->set('quickCustomer.business_type_id', $businessType->id)
        ->call('createQuickCustomer')
        ->assertHasNoErrors();

    $newCustomer = CustomerMaster::where('phone', '9000111222')->firstOrFail();
    expect($newCustomer->first_name)->toBe('WIZARD');

    Livewire::test(Edit::class)
        ->set('quickCustomer.first_name', 'WIZARD2')
        ->set('quickCustomer.phone', '9000111223')
        ->set('quickCustomer.business_type_id', $businessType->id)
        ->call('createQuickCustomer')
        ->assertSet('customer_id', CustomerMaster::where('phone', '9000111223')->value('id'));
});

it('quick-add Vehicle wizard creates brand + model + variant chain and assigns variant_id', function () {
    $brand = VehicleBrandMaster::firstOrCreate(['name' => 'HYUNDAI'], ['is_active' => true]);
    $model = VehicleModelMaster::firstOrCreate(
        ['brand_id' => $brand->id, 'name' => 'CRETA'],
        ['is_active' => true],
    );

    $fuel = FuelTypeMaster::factory()->create(['name' => 'PETROL']);

    Livewire::test(Edit::class)
        ->set('quickVehicle.brand_id', $brand->id)
        ->set('quickVehicle.model_id', $model->id)
        ->set('quickVehicle.name', 'sx 2024')
        ->set('quickVehicle.fuel_type_id', $fuel->id)
        ->set('quickVehicle.year', 2024)
        ->call('createQuickVehicle')
        ->assertHasNoErrors();

    $variant = VehicleVariantMaster::where('name', 'SX 2024')->where('model_id', $model->id)->firstOrFail();

    expect($variant->fuel_type_id)->toBe($fuel->id)
        ->and($variant->year)->toBe(2024);

    Livewire::test(Edit::class)
        ->set('quickVehicle.brand_id', $brand->id)
        ->set('quickVehicle.model_id', $model->id)
        ->set('quickVehicle.name', 'sx 2024')
        ->call('createQuickVehicle')
        ->assertSet('variant_id', $variant->id);
});

it('quick-add Vehicle wizard requires brand + model + variant name', function () {
    Livewire::test(Edit::class)
        ->call('createQuickVehicle')
        ->assertHasErrors([
            'quickVehicle.brand_id',
            'quickVehicle.model_id',
            'quickVehicle.name',
        ]);
});

it('createQuickVehicleBrand creates a brand inline and resets the model selection', function () {
    Livewire::test(Edit::class)
        ->set('quickVehicleBrandSearch', 'kia')
        ->call('createQuickVehicleBrand')
        ->assertSet('quickVehicle.model_id', null)
        ->assertSet('quickVehicleBrandSearch', '');

    expect(VehicleBrandMaster::where('name', 'KIA')->exists())->toBeTrue();
});

it('createQuickVehicleModel needs a brand picked first', function () {
    Livewire::test(Edit::class)
        ->set('quickVehicleModelSearch', 'newmodel')
        ->call('createQuickVehicleModel')
        ->assertHasErrors(['quickVehicle.brand_id']);
});

it('createQuickVehicleModel creates a model under the selected brand', function () {
    $brand = VehicleBrandMaster::firstOrCreate(['name' => 'TATA'], ['is_active' => true]);

    Livewire::test(Edit::class)
        ->set('quickVehicle.brand_id', $brand->id)
        ->set('quickVehicleModelSearch', 'punch')
        ->call('createQuickVehicleModel')
        ->assertSet('quickVehicleModelSearch', '');

    $model = VehicleModelMaster::where('brand_id', $brand->id)->where('name', 'PUNCH')->first();
    expect($model)->not->toBeNull();
});

it('deletes a customer vehicle', function () {
    $r = CustomerVehicleMaster::factory()->create();
    Livewire::test(Index::class)->call('delete', $r->id);
    expect(CustomerVehicleMaster::find($r->id))->toBeNull();
});

it('requires authentication', function () {
    auth()->logout();
    $this->get(route('customer-vehicle-master.index'))->assertRedirect(route('login'));
});

it('searches customers server-side instead of rendering the whole table', function () {
    CustomerMaster::factory()->count(30)->create();
    $needle = CustomerMaster::factory()->create(['first_name' => 'ZZQQ', 'last_name' => 'FINDME']);

    $component = Livewire::test(Edit::class);

    // Unsearched list is capped, not the full table.
    expect($component->instance()->customers)->toHaveCount(20);

    $component->set('customerSearch', 'ZZQQ');
    expect($component->instance()->customers->pluck('id'))->toContain($needle->id)
        ->and($component->instance()->customers->count())->toBeLessThan(5);
});

it('keeps the selected customer in the option list even when it does not match the search', function () {
    CustomerMaster::factory()->count(30)->create();
    $selected = CustomerMaster::factory()->create(['first_name' => 'AAAA', 'last_name' => 'OWNER']);
    $vehicle = CustomerVehicleMaster::factory()->create(['customer_id' => $selected->id]);

    $component = Livewire::test(Edit::class, ['customer_vehicle' => $vehicle])
        ->set('customerSearch', 'ZZZZ-NO-MATCH');

    // Otherwise the edit form would render a blank picker for a value it holds.
    expect($component->instance()->customers->pluck('id'))->toContain($selected->id);
});

it('still offers a selected customer who has since been deactivated', function () {
    $selected = CustomerMaster::factory()->create(['is_active' => false]);
    $vehicle = CustomerVehicleMaster::factory()->create(['customer_id' => $selected->id]);

    $component = Livewire::test(Edit::class, ['customer_vehicle' => $vehicle]);

    expect($component->instance()->customers->pluck('id'))->toContain($selected->id);
});

it('searches vehicle variants by brand and by model, not just variant name', function () {
    $brand = VehicleBrandMaster::factory()->create(['name' => 'QQBRAND']);
    $model = VehicleModelMaster::factory()->create(['name' => 'QQMODEL', 'brand_id' => $brand->id]);
    $variant = VehicleVariantMaster::factory()->create(['name' => 'VXI', 'model_id' => $model->id]);
    VehicleVariantMaster::factory()->count(25)->create();

    $component = Livewire::test(Edit::class);

    $component->set('vehicleSearch', 'QQBRAND');
    expect($component->instance()->vehicles->pluck('id'))->toContain($variant->id);

    $component->set('vehicleSearch', 'QQMODEL');
    expect($component->instance()->vehicles->pluck('id'))->toContain($variant->id);
});

it('validates the registration number as it is typed', function () {
    $existing = CustomerVehicleMaster::factory()->create(['registration_no' => 'GJ05RH4816']);

    // Duplicate surfaces immediately, without submitting.
    Livewire::test(Edit::class)
        ->set('registration_no', 'GJ05RH4816')
        ->assertHasErrors(['registration_no']);

    // Malformed plate surfaces too.
    Livewire::test(Edit::class)
        ->set('registration_no', 'NOTAPLATE99')
        ->assertHasErrors(['registration_no']);

    // A half-typed plate is not nagged about.
    Livewire::test(Edit::class)
        ->set('registration_no', 'GJ05')
        ->assertHasNoErrors(['registration_no']);

    // Spaces are normalised away and a valid, unused plate passes.
    Livewire::test(Edit::class)
        ->set('registration_no', 'gj05 rh 4817')
        ->assertSet('registration_no', 'GJ05RH4817')
        ->assertHasNoErrors(['registration_no']);
});

it('does not require a registration number on the unregistered plate type', function () {
    $unregistered = RegistrationTypeMaster::firstOrCreate(['name' => 'UNREGISTERED'], ['code' => 'UNREG', 'is_active' => true]);
    $customer = CustomerMaster::factory()->create();
    $variant = VehicleVariantMaster::factory()->create();

    Livewire::test(Edit::class)
        ->set('customer_id', $customer->id)
        ->set('variant_id', $variant->id)
        ->set('registration_type_id', $unregistered->id)
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect(route('customer-vehicle-master.index'));

    // Stored as NULL, not '' — the column is unique, so blanks would collide.
    expect(CustomerVehicleMaster::firstOrFail()->registration_no)->toBeNull();
});

it('lets two unregistered vehicles coexist without colliding on the unique plate', function () {
    $unregistered = RegistrationTypeMaster::firstOrCreate(['name' => 'UNREGISTERED'], ['code' => 'UNREG', 'is_active' => true]);
    $customer = CustomerMaster::factory()->create();

    foreach ([VehicleVariantMaster::factory()->create(), VehicleVariantMaster::factory()->create()] as $variant) {
        Livewire::test(Edit::class)
            ->set('customer_id', $customer->id)
            ->set('variant_id', $variant->id)
            ->set('registration_type_id', $unregistered->id)
            ->call('save')
            ->assertHasNoErrors();
    }

    expect(CustomerVehicleMaster::whereNull('registration_no')->count())->toBe(2);
});

it('drops a typed plate when the type switches to unregistered', function () {
    $unregistered = RegistrationTypeMaster::firstOrCreate(['name' => 'UNREGISTERED'], ['code' => 'UNREG', 'is_active' => true]);

    $component = Livewire::test(Edit::class)
        ->set('registration_no', 'GJ05AA1234')
        ->set('registration_type_id', $unregistered->id);

    expect($component->get('registration_no'))->toBeNull()
        ->and($component->instance()->isUnregisteredPlate())->toBeTrue();
});

it('still demands a valid plate on every other type', function () {
    $private = RegistrationTypeMaster::firstOrCreate(['name' => 'PRIVATE'], ['code' => 'PVT', 'is_active' => true]);
    $customer = CustomerMaster::factory()->create();
    $variant = VehicleVariantMaster::factory()->create();

    Livewire::test(Edit::class)
        ->set('customer_id', $customer->id)
        ->set('variant_id', $variant->id)
        ->set('registration_type_id', $private->id)
        ->call('save')
        ->assertHasErrors(['registration_no']);
});

it('shows unregistered instead of a blank plate in the list', function () {
    $unregistered = RegistrationTypeMaster::firstOrCreate(['name' => 'UNREGISTERED'], ['code' => 'UNREG', 'is_active' => true]);
    CustomerVehicleMaster::factory()->create([
        'registration_no' => null, 'registration_type_id' => $unregistered->id,
    ]);

    Livewire::test(Index::class)->assertSee('Unregistered');
});

it('preselects the customer when arriving from the customer form', function () {
    $customer = CustomerMaster::factory()->create();

    Livewire::test(Edit::class, ['for-customer' => $customer->id])
        ->assertSet('customer_id', $customer->id);
});

it('ignores a for-customer id that does not exist', function () {
    Livewire::test(Edit::class, ['for-customer' => 999999])
        ->assertSet('customer_id', null);
});
