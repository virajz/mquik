<?php

use App\Modules\CustomerMaster\Models\CustomerMaster;
use App\Modules\CustomerVehicleMaster\Livewire\Edit;
use App\Modules\CustomerVehicleMaster\Livewire\Index;
use App\Modules\CustomerVehicleMaster\Models\CustomerVehicleMaster;
use App\Modules\VehicleColorMaster\Models\VehicleColorMaster;
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
        ->and($r->number_plate_type)->toBe('private')
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

    Livewire::test(Edit::class)
        ->set('customer_id', $customer->id)
        ->set('variant_id', $variant->id)
        ->set('number_plate_type', 'bh_series')
        ->set('registration_no', '24BH1234AA')
        ->call('save')
        ->assertHasNoErrors();

    expect(CustomerVehicleMaster::firstOrFail()->registration_no)->toBe('24BH1234AA');
});

it('rejects an invalid plate type', function () {
    $customer = CustomerMaster::factory()->create();
    $variant = VehicleVariantMaster::factory()->create();

    Livewire::test(Edit::class)
        ->set('customer_id', $customer->id)
        ->set('variant_id', $variant->id)
        ->set('registration_no', 'GJ05AA1234')
        ->set('number_plate_type', 'galaxy')
        ->call('save')
        ->assertHasErrors(['number_plate_type']);
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

it('deletes a customer vehicle', function () {
    $r = CustomerVehicleMaster::factory()->create();
    Livewire::test(Index::class)->call('delete', $r->id);
    expect(CustomerVehicleMaster::find($r->id))->toBeNull();
});

it('requires authentication', function () {
    auth()->logout();
    $this->get(route('customer-vehicle-master.index'))->assertRedirect(route('login'));
});
