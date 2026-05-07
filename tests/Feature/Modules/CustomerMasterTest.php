<?php

use App\Modules\CustomerMaster\Livewire\Form;
use App\Modules\CustomerMaster\Livewire\Index;
use App\Modules\CustomerMaster\Models\CustomerMaster;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(adminUser());
});

it('renders the index page', function () {
    CustomerMaster::factory()->count(3)->create();

    $this->get(route('customer-master.index'))
        ->assertOk()
        ->assertSeeLivewire(Index::class);
});

it('searches by name, phone, email, aadhar, or pan', function () {
    CustomerMaster::factory()->create(['name' => 'RAVI SHARMA', 'phone' => '9876543210', 'email' => 'ravi@example.com']);
    CustomerMaster::factory()->create(['name' => 'PRIYA PATEL', 'phone' => '9123456789', 'aadhar' => '111122223333']);
    CustomerMaster::factory()->create(['name' => 'ARJUN MEHTA', 'phone' => '9000000000', 'pan' => 'ABCDE1234F']);

    Livewire::test(Index::class)->set('search', 'RAVI')
        ->assertSee('RAVI SHARMA')
        ->assertDontSee('PRIYA PATEL');

    Livewire::test(Index::class)->set('search', '9123456789')
        ->assertSee('PRIYA PATEL')
        ->assertDontSee('RAVI SHARMA');

    Livewire::test(Index::class)->set('search', 'ABCDE1234F')
        ->assertSee('ARJUN MEHTA');
});

it('filters by customer type', function () {
    CustomerMaster::factory()->create(['name' => 'WALKING ONE']);
    CustomerMaster::factory()->loyal()->create(['name' => 'LOYAL ONE']);
    CustomerMaster::factory()->corporate()->create(['name' => 'CORPORATE ONE']);

    Livewire::test(Index::class)->set('typeFilter', 'loyal')
        ->assertSee('LOYAL ONE')
        ->assertDontSee('WALKING ONE')
        ->assertDontSee('CORPORATE ONE');

    Livewire::test(Index::class)->set('typeFilter', 'corporate')
        ->assertSee('CORPORATE ONE')
        ->assertDontSee('WALKING ONE');
});

it('filters by active status', function () {
    CustomerMaster::factory()->create(['name' => 'ACTIVE NEHA']);
    CustomerMaster::factory()->inactive()->create(['name' => 'OLD KARTHIK']);

    Livewire::test(Index::class)->set('statusFilter', 'active')
        ->assertSee('ACTIVE NEHA')
        ->assertDontSee('OLD KARTHIK');

    Livewire::test(Index::class)->set('statusFilter', 'inactive')
        ->assertSee('OLD KARTHIK')
        ->assertDontSee('ACTIVE NEHA');
});

it('creates a customer with full details', function () {
    Livewire::test(Form::class)
        ->set('name', 'ravi sharma')
        ->set('customer_type', 'loyal')
        ->set('phone', '9876543210')
        ->set('email', 'ravi@example.com')
        ->set('address', 'satellite road')
        ->set('city', 'ahmedabad')
        ->set('pincode', '380015')
        ->set('aadhar', '111122223333')
        ->set('pan', 'ABCDE1234F')
        ->set('date_of_birth', '1990-05-15')
        ->call('save')
        ->assertHasNoErrors()
        ->assertDispatched('customer-master:saved');

    $r = CustomerMaster::firstOrFail();
    expect($r->name)->toBe('RAVI SHARMA')
        ->and($r->customer_type)->toBe('loyal')
        ->and($r->phone)->toBe('9876543210')         // not uppercased
        ->and($r->email)->toBe('ravi@example.com')   // not uppercased
        ->and($r->city)->toBe('AHMEDABAD')
        ->and($r->pincode)->toBe('380015')
        ->and($r->aadhar)->toBe('111122223333')      // not uppercased
        ->and($r->pan)->toBe('ABCDE1234F')
        ->and($r->is_active)->toBeTrue();
});

it('updates an existing customer', function () {
    $r = CustomerMaster::factory()->create(['name' => 'OLD NAME', 'customer_type' => 'walking']);

    Livewire::test(Form::class)
        ->dispatch('customer-master:edit', id: $r->id)
        ->set('name', 'updated name')
        ->set('customer_type', 'loyal')
        ->call('save')
        ->assertHasNoErrors();

    $fresh = $r->fresh();
    expect($fresh->name)->toBe('UPDATED NAME')
        ->and($fresh->customer_type)->toBe('loyal');
});

it('deletes a customer from the index', function () {
    $r = CustomerMaster::factory()->create();

    Livewire::test(Index::class)->call('delete', $r->id);

    expect(CustomerMaster::find($r->id))->toBeNull();
});

it('validates required fields', function () {
    Livewire::test(Form::class)
        ->set('name', '')
        ->set('phone', '')
        ->call('save')
        ->assertHasErrors(['name' => 'required', 'phone' => 'required']);
});

it('validates PAN format', function () {
    Livewire::test(Form::class)
        ->set('name', 'TEST')
        ->set('phone', '9876543210')
        ->set('pan', 'INVALID-PAN')
        ->call('save')
        ->assertHasErrors(['pan']);
});

it('validates aadhar must be exactly 12 chars if provided', function () {
    Livewire::test(Form::class)
        ->set('name', 'TEST')
        ->set('phone', '9876543210')
        ->set('aadhar', '123')
        ->call('save')
        ->assertHasErrors(['aadhar']);
});

it('rejects duplicate aadhar', function () {
    CustomerMaster::factory()->create(['aadhar' => '111122223333']);

    Livewire::test(Form::class)
        ->set('name', 'TEST')
        ->set('phone', '9876543210')
        ->set('aadhar', '111122223333')
        ->call('save')
        ->assertHasErrors(['aadhar']);
});

it('allows updating own record without triggering self-uniqueness on aadhar', function () {
    $r = CustomerMaster::factory()->create(['aadhar' => '111122223333']);

    Livewire::test(Form::class)
        ->dispatch('customer-master:edit', id: $r->id)
        ->set('aadhar', '111122223333')  // same value
        ->call('save')
        ->assertHasNoErrors();
});

it('requires authentication', function () {
    auth()->logout();

    $this->get(route('customer-master.index'))->assertRedirect(route('login'));
});
