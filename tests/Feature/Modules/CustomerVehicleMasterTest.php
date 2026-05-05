<?php

use App\Models\User;
use App\Modules\CustomerMaster\Models\CustomerMaster;
use App\Modules\CustomerVehicleMaster\Livewire\Form;
use App\Modules\CustomerVehicleMaster\Livewire\Index;
use App\Modules\CustomerVehicleMaster\Models\CustomerVehicleMaster;
use App\Modules\VehicleColorMaster\Models\VehicleColorMaster;
use App\Modules\VehicleModelMaster\Models\VehicleModelMaster;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(User::factory()->create());
});

it('renders the index page', function () {
    CustomerVehicleMaster::factory()->count(3)->create();
    $this->get(route('customer-vehicle-master.index'))->assertOk()->assertSeeLivewire(Index::class);
});

it('creates a customer vehicle with all FKs', function () {
    $customer = CustomerMaster::factory()->create();
    $model = VehicleModelMaster::factory()->create();
    $color = VehicleColorMaster::factory()->create();

    Livewire::test(Form::class)
        ->set('customer_id', $customer->id)
        ->set('model_id', $model->id)
        ->set('color_id', $color->id)
        ->set('registration_no', 'gj 05 aa 1234')
        ->set('year_of_manufacture', 2022)
        ->set('odometer_km', 45000)
        ->call('save')
        ->assertHasNoErrors()
        ->assertDispatched('customer-vehicle-master:saved');

    $r = CustomerVehicleMaster::firstOrFail();
    expect($r->customer_id)->toBe($customer->id)
        ->and($r->model_id)->toBe($model->id)
        ->and($r->color_id)->toBe($color->id)
        ->and($r->registration_no)->toBe('GJ 05 AA 1234')  // uppercased
        ->and($r->year_of_manufacture)->toBe(2022)
        ->and($r->odometer_km)->toBe(45000);
});

it('requires customer_id, model_id, and registration_no', function () {
    Livewire::test(Form::class)
        ->call('save')
        ->assertHasErrors(['customer_id', 'model_id', 'registration_no']);
});

it('rejects duplicate registration_no', function () {
    $existing = CustomerVehicleMaster::factory()->create(['registration_no' => 'GJ 05 AA 1234']);
    $customer = CustomerMaster::factory()->create();
    $model = VehicleModelMaster::factory()->create();

    Livewire::test(Form::class)
        ->set('customer_id', $customer->id)
        ->set('model_id', $model->id)
        ->set('registration_no', 'GJ 05 AA 1234')
        ->call('save')
        ->assertHasErrors(['registration_no']);
});

it('validates VIN is exactly 17 chars when provided', function () {
    $customer = CustomerMaster::factory()->create();
    $model = VehicleModelMaster::factory()->create();

    Livewire::test(Form::class)
        ->set('customer_id', $customer->id)
        ->set('model_id', $model->id)
        ->set('registration_no', 'GJ 05 ZZ 9999')
        ->set('vin', 'TOO-SHORT')
        ->call('save')
        ->assertHasErrors(['vin']);
});

it('clears variant_id when model is changed', function () {
    $customer = CustomerMaster::factory()->create();
    $model = VehicleModelMaster::factory()->create();
    $newModel = VehicleModelMaster::factory()->create();

    Livewire::test(Form::class)
        ->set('customer_id', $customer->id)
        ->set('model_id', $model->id)
        ->set('variant_id', 99) // pretend it's set
        ->set('model_id', $newModel->id) // triggers updatedModelId
        ->assertSet('variant_id', null);
});

it('searches by reg no, VIN, customer name, or phone', function () {
    $abc = CustomerMaster::factory()->create(['name' => 'RAVI ABC', 'phone' => '9999000001']);
    CustomerVehicleMaster::factory()->create(['customer_id' => $abc->id, 'registration_no' => 'GJ 05 AA 1234']);

    $xyz = CustomerMaster::factory()->create(['name' => 'PRIYA XYZ', 'phone' => '9999000002']);
    CustomerVehicleMaster::factory()->create(['customer_id' => $xyz->id, 'registration_no' => 'MH 12 BB 5678']);

    Livewire::test(Index::class)->set('search', 'GJ 05 AA')->assertSee('GJ 05 AA 1234')->assertDontSee('MH 12 BB 5678');
    Livewire::test(Index::class)->set('search', 'PRIYA XYZ')->assertSee('MH 12 BB 5678')->assertDontSee('GJ 05 AA 1234');
    Livewire::test(Index::class)->set('search', '9999000001')->assertSee('GJ 05 AA 1234');
});

it('updates a customer vehicle', function () {
    $r = CustomerVehicleMaster::factory()->create(['registration_no' => 'GJ 05 OLD 0000']);
    Livewire::test(Form::class)
        ->dispatch('customer-vehicle-master:edit', id: $r->id)
        ->set('registration_no', 'GJ 05 NEW 9999')
        ->call('save')
        ->assertHasNoErrors();
    expect($r->fresh()->registration_no)->toBe('GJ 05 NEW 9999');
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
