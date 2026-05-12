<?php

use App\Modules\BusinessTypeMaster\Models\BusinessTypeMaster;
use App\Modules\CustomerMaster\Livewire\Edit;
use App\Modules\CustomerMaster\Livewire\Index;
use App\Modules\CustomerMaster\Models\CustomerAddress;
use App\Modules\CustomerMaster\Models\CustomerMaster;
use App\Modules\RegionMaster\Models\RegionMaster;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(adminUser());
    $this->walking = BusinessTypeMaster::firstOrCreate(['name' => 'WALKING'], ['is_active' => true]);
    $this->loyal = BusinessTypeMaster::firstOrCreate(['name' => 'LOYAL'], ['is_active' => true]);
    $this->corporate = BusinessTypeMaster::firstOrCreate(['name' => 'CORPORATE'], ['is_active' => true]);
});

it('renders the index page', function () {
    CustomerMaster::factory()->count(3)->create();

    $this->get(route('customer-master.index'))
        ->assertOk()
        ->assertSeeLivewire(Index::class);
});

it('renders the create page', function () {
    $this->get(route('customer-master.create'))
        ->assertOk()
        ->assertSeeLivewire(Edit::class)
        ->assertSee('New Customer');
});

it('renders the edit page for an existing customer', function () {
    $customer = CustomerMaster::factory()->create(['name' => 'NEHA SHARMA']);

    $this->get(route('customer-master.edit', $customer))
        ->assertOk()
        ->assertSeeLivewire(Edit::class)
        ->assertSee('NEHA SHARMA');
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

    Livewire::test(Index::class)->set('typeFilter', (string) $this->loyal->id)
        ->assertSee('LOYAL ONE')
        ->assertDontSee('WALKING ONE')
        ->assertDontSee('CORPORATE ONE');

    Livewire::test(Index::class)->set('typeFilter', (string) $this->corporate->id)
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
    Livewire::test(Edit::class)
        ->set('name', 'ravi sharma')
        ->set('business_type_id', $this->loyal->id)
        ->set('phone', '9876543210')
        ->set('email', 'ravi@example.com')
        ->set('aadhar', '111122223333')
        ->set('pan', 'ABCDE1234F')
        ->set('date_of_birth', '1990-05-15')
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect(route('customer-master.index'));

    $r = CustomerMaster::firstOrFail();
    expect($r->name)->toBe('RAVI SHARMA')
        ->and($r->business_type_id)->toBe($this->loyal->id)
        ->and($r->phone)->toBe('9876543210')         // not uppercased
        ->and($r->email)->toBe('ravi@example.com')   // not uppercased
        ->and($r->aadhar)->toBe('111122223333')      // not uppercased
        ->and($r->pan)->toBe('ABCDE1234F')
        ->and($r->is_active)->toBeTrue();
});

it('creates a customer with multiple addresses and one primary', function () {
    $state = RegionMaster::firstOrCreate(['kind' => 'state', 'parent_id' => null, 'name' => 'GUJARAT'], ['code' => 'GJ', 'is_active' => true]);
    $city = RegionMaster::firstOrCreate(['kind' => 'city', 'parent_id' => $state->id, 'name' => 'AHMEDABAD'], ['code' => 'AHD', 'is_active' => true]);
    $area = RegionMaster::firstOrCreate(['kind' => 'area', 'parent_id' => $city->id, 'name' => 'SATELLITE'], ['is_active' => true]);
    $pincode = RegionMaster::firstOrCreate(['kind' => 'pincode', 'parent_id' => $area->id, 'name' => '380015'], ['is_active' => true]);

    Livewire::test(Edit::class)
        ->set('name', 'ravi sharma')
        ->set('business_type_id', $this->loyal->id)
        ->set('phone', '9876543210')
        ->set('addresses', [
            ['id' => null, 'label' => 'home', 'address_line' => '12 lake view', 'region_id' => $pincode->id, 'is_primary' => true],
            ['id' => null, 'label' => 'office', 'address_line' => 'tower b, 5th floor', 'region_id' => $city->id, 'is_primary' => false],
        ])
        ->call('save')
        ->assertHasNoErrors();

    $customer = CustomerMaster::with('addresses')->firstOrFail();
    expect($customer->addresses)->toHaveCount(2);

    $primary = $customer->addresses->firstWhere('is_primary', true);
    expect($primary->label)->toBe('HOME')
        ->and($primary->address_line)->toBe('12 LAKE VIEW')
        ->and($primary->region_id)->toBe($pincode->id);

    $secondary = $customer->addresses->firstWhere('is_primary', false);
    expect($secondary->label)->toBe('OFFICE')
        ->and($secondary->region_id)->toBe($city->id);
});

it('makes the first address primary if none flagged', function () {
    Livewire::test(Edit::class)
        ->set('name', 'A')
        ->set('business_type_id', $this->walking->id)
        ->set('phone', '9876543210')
        ->set('addresses', [
            ['id' => null, 'label' => null, 'address_line' => 'A LINE', 'region_id' => null, 'is_primary' => false],
            ['id' => null, 'label' => null, 'address_line' => 'B LINE', 'region_id' => null, 'is_primary' => false],
        ])
        ->call('save')
        ->assertHasNoErrors();

    $customer = CustomerMaster::with('addresses')->firstOrFail();
    expect($customer->addresses->where('is_primary', true))->toHaveCount(1)
        ->and($customer->addresses->firstWhere('is_primary', true)->address_line)->toBe('A LINE');
});

it('drops blank address rows on save', function () {
    Livewire::test(Edit::class)
        ->set('name', 'A')
        ->set('business_type_id', $this->walking->id)
        ->set('phone', '9876543210')
        ->set('addresses', [
            ['id' => null, 'label' => null, 'address_line' => 'KEEP ME', 'region_id' => null, 'is_primary' => true],
            ['id' => null, 'label' => null, 'address_line' => null, 'region_id' => null, 'is_primary' => false],
        ])
        ->call('save')
        ->assertHasNoErrors();

    expect(CustomerAddress::count())->toBe(1);
});

it('replaces removed addresses on edit', function () {
    $customer = CustomerMaster::factory()->create();
    $a = CustomerAddress::factory()->primary()->create(['customer_id' => $customer->id, 'address_line' => 'OLD LINE A']);
    $b = CustomerAddress::factory()->secondary()->create(['customer_id' => $customer->id, 'address_line' => 'OLD LINE B']);

    Livewire::test(Edit::class, ['customer' => $customer])
        ->set('addresses', [
            ['id' => $a->id, 'label' => null, 'address_line' => 'UPDATED A', 'region_id' => null, 'is_primary' => true],
        ])
        ->call('save')
        ->assertHasNoErrors();

    expect(CustomerAddress::find($b->id))->toBeNull()
        ->and(CustomerAddress::find($a->id)->address_line)->toBe('UPDATED A');
});

it('updates an existing customer', function () {
    $r = CustomerMaster::factory()->create(['name' => 'OLD NAME', 'business_type_id' => $this->walking->id]);

    Livewire::test(Edit::class, ['customer' => $r])
        ->set('name', 'updated name')
        ->set('business_type_id', $this->loyal->id)
        ->call('save')
        ->assertHasNoErrors();

    $fresh = $r->fresh();
    expect($fresh->name)->toBe('UPDATED NAME')
        ->and($fresh->business_type_id)->toBe($this->loyal->id);
});

it('deletes a customer from the index', function () {
    $r = CustomerMaster::factory()->create();

    Livewire::test(Index::class)->call('delete', $r->id);

    expect(CustomerMaster::find($r->id))->toBeNull();
});

it('validates required fields', function () {
    Livewire::test(Edit::class)
        ->set('name', '')
        ->set('phone', '')
        ->call('save')
        ->assertHasErrors(['name' => 'required', 'phone' => 'required']);
});

it('validates PAN format', function () {
    Livewire::test(Edit::class)
        ->set('name', 'TEST')
        ->set('phone', '9876543210')
        ->set('pan', 'INVALID-PAN')
        ->call('save')
        ->assertHasErrors(['pan']);
});

it('validates aadhar must be exactly 12 chars if provided', function () {
    Livewire::test(Edit::class)
        ->set('name', 'TEST')
        ->set('phone', '9876543210')
        ->set('aadhar', '123')
        ->call('save')
        ->assertHasErrors(['aadhar']);
});

it('rejects duplicate aadhar', function () {
    CustomerMaster::factory()->create(['aadhar' => '111122223333']);

    Livewire::test(Edit::class)
        ->set('name', 'TEST')
        ->set('phone', '9876543210')
        ->set('aadhar', '111122223333')
        ->call('save')
        ->assertHasErrors(['aadhar']);
});

it('allows updating own record without triggering self-uniqueness on aadhar', function () {
    $r = CustomerMaster::factory()->create(['aadhar' => '111122223333']);

    Livewire::test(Edit::class, ['customer' => $r])
        ->set('aadhar', '111122223333')  // same value
        ->call('save')
        ->assertHasNoErrors();
});

it('requires authentication', function () {
    auth()->logout();

    $this->get(route('customer-master.index'))->assertRedirect(route('login'));
});
