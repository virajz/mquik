<?php

use App\Modules\BusinessTypeMaster\Models\BusinessTypeMaster;
use App\Modules\CustomerMaster\Livewire\Edit;
use App\Modules\CustomerMaster\Livewire\Index;
use App\Modules\CustomerMaster\Models\CustomerAddress;
use App\Modules\CustomerMaster\Models\CustomerMaster;
use App\Modules\RegionMaster\Models\RegionMaster;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
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
        ->set('first_name', 'ravi')
        ->set('last_name', 'sharma')
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
        ->and($r->first_name)->toBe('RAVI')
        ->and($r->last_name)->toBe('SHARMA')
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
        ->set('first_name', 'ravi')
        ->set('last_name', 'sharma')
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
        ->set('first_name', 'A')
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
        ->set('first_name', 'A')
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

it('records a customer referral', function () {
    $referrer = CustomerMaster::factory()->create(['first_name' => 'REFERRER', 'last_name' => 'ONE', 'phone' => '9000111222']);

    Livewire::test(Edit::class)
        ->set('first_name', 'NEW')
        ->set('last_name', 'CUSTOMER')
        ->set('business_type_id', $this->walking->id)
        ->set('phone', '9876543210')
        ->set('referred_by_customer_id', $referrer->id)
        ->call('save')
        ->assertHasNoErrors();

    $created = CustomerMaster::with('referredBy')->where('phone', '9876543210')->firstOrFail();
    expect($created->referred_by_customer_id)->toBe($referrer->id)
        ->and($created->referredBy->name)->toBe('REFERRER ONE')
        ->and($referrer->refresh()->referrals)->toHaveCount(1);
});

it('create-option: creates a new business type from the Type combobox and selects it', function () {
    Livewire::test(Edit::class)
        ->set('businessTypeSearch', 'fleet')
        ->call('createBusinessType')
        ->assertHasNoErrors();

    $type = BusinessTypeMaster::where('name', 'FLEET')->firstOrFail();

    Livewire::test(Edit::class)
        ->set('businessTypeSearch', 'fleet')
        ->call('createBusinessType')
        ->assertSet('business_type_id', $type->id)
        ->assertSet('businessTypeSearch', '');
});

it('create-option: re-typing an existing name selects the existing type, no duplicate', function () {
    $existing = BusinessTypeMaster::create(['name' => 'FLEET', 'is_active' => true]);

    Livewire::test(Edit::class)
        ->set('businessTypeSearch', 'fleet')
        ->call('createBusinessType')
        ->assertSet('business_type_id', $existing->id);

    expect(BusinessTypeMaster::where('name', 'FLEET')->count())->toBe(1);
});

it('create-option: empty search is a no-op', function () {
    $countBefore = BusinessTypeMaster::count();

    Livewire::test(Edit::class)
        ->set('businessTypeSearch', '   ')
        ->call('createBusinessType')
        ->assertSet('business_type_id', null);

    expect(BusinessTypeMaster::count())->toBe($countBefore);
});

it('create-option region: creates a new pincode for the selected address row', function () {
    $component = Livewire::test(Edit::class);

    // Type into row 0's region search and click "Create as Pincode"
    $component
        ->set('addresses.0.regionSearch', '380058')
        ->call('createRegionForAddress', 0, 'pincode');

    $region = RegionMaster::where('kind', 'pincode')->where('name', '380058')->firstOrFail();

    $component
        ->assertSet('addresses.0.region_id', $region->id)
        ->assertSet('addresses.0.regionSearch', '');
});

it('create-option region: re-typing an existing kind+name selects the existing row, no duplicate', function () {
    $existing = RegionMaster::create(['kind' => 'city', 'name' => 'AHMEDABAD', 'parent_id' => null, 'is_active' => true]);

    Livewire::test(Edit::class)
        ->set('addresses.0.regionSearch', 'ahmedabad')
        ->call('createRegionForAddress', 0, 'city')
        ->assertSet('addresses.0.region_id', $existing->id);

    expect(RegionMaster::where('kind', 'city')->where('name', 'AHMEDABAD')->where('parent_id', null)->count())->toBe(1);
});

it('create-option region: invalid kind is a no-op', function () {
    $countBefore = RegionMaster::count();

    Livewire::test(Edit::class)
        ->set('addresses.0.regionSearch', 'BOGUS')
        ->call('createRegionForAddress', 0, 'galaxy')
        ->assertSet('addresses.0.region_id', null);

    expect(RegionMaster::count())->toBe($countBefore);
});

it('create-option region: empty search is a no-op', function () {
    $countBefore = RegionMaster::count();

    Livewire::test(Edit::class)
        ->set('addresses.0.regionSearch', '   ')
        ->call('createRegionForAddress', 0, 'pincode')
        ->assertSet('addresses.0.region_id', null);

    expect(RegionMaster::count())->toBe($countBefore);
});

it('saves a secondary email and rejects it being identical to the primary', function () {
    Livewire::test(Edit::class)
        ->set('first_name', 'A')
        ->set('business_type_id', $this->walking->id)
        ->set('phone', '9876543210')
        ->set('email', 'primary@test.com')
        ->set('secondary_email', 'primary@test.com')
        ->call('save')
        ->assertHasErrors(['secondary_email']);

    Livewire::test(Edit::class)
        ->set('first_name', 'A')
        ->set('business_type_id', $this->walking->id)
        ->set('phone', '9876543210')
        ->set('email', 'primary@test.com')
        ->set('secondary_email', 'backup@test.com')
        ->call('save')
        ->assertHasNoErrors();

    expect(CustomerMaster::firstOrFail()->secondary_email)->toBe('backup@test.com');
});

it('uploads aadhar file, persists path + original filename, can stream download', function () {
    Storage::fake();

    $upload = UploadedFile::fake()->image('My Aadhar.jpg', 800, 600);

    Livewire::test(Edit::class)
        ->set('first_name', 'A')
        ->set('business_type_id', $this->walking->id)
        ->set('phone', '9876543210')
        ->set('aadhar_file', $upload)
        ->call('save')
        ->assertHasNoErrors();

    $customer = CustomerMaster::firstOrFail();
    expect($customer->aadhar_file_path)->toStartWith("customers/{$customer->id}/aadhar/")
        ->and($customer->aadhar_file_name)->toBe('My Aadhar.jpg');

    Storage::disk(config('filesystems.default'))->assertExists($customer->aadhar_file_path);

    // Secured download serves the original filename (Symfony quotes spaces).
    $response = $this->get(route('customer-master.file', ['customer' => $customer, 'type' => 'aadhar']))
        ->assertOk();

    expect($response->headers->get('content-disposition'))->toContain('My Aadhar.jpg');
});

it('replacing an aadhar file deletes the old one from storage', function () {
    Storage::fake();
    $customer = CustomerMaster::factory()->create();
    $oldUpload = UploadedFile::fake()->image('old.jpg');
    $oldPath = $oldUpload->store("customers/{$customer->id}/aadhar");
    $customer->forceFill(['aadhar_file_path' => $oldPath, 'aadhar_file_name' => 'old.jpg'])->save();

    Livewire::test(Edit::class, ['customer' => $customer])
        ->set('aadhar_file', UploadedFile::fake()->image('new.jpg'))
        ->call('save')
        ->assertHasNoErrors();

    Storage::assertMissing($oldPath);

    $fresh = $customer->fresh();
    expect($fresh->aadhar_file_name)->toBe('new.jpg')
        ->and($fresh->aadhar_file_path)->not->toBe($oldPath);
    Storage::assertExists($fresh->aadhar_file_path);
});

it('removing an existing aadhar file wipes path + name + storage on save', function () {
    Storage::fake();
    $customer = CustomerMaster::factory()->create();
    $existingPath = UploadedFile::fake()->image('keep.jpg')->store("customers/{$customer->id}/aadhar");
    $customer->forceFill(['aadhar_file_path' => $existingPath, 'aadhar_file_name' => 'keep.jpg'])->save();

    Livewire::test(Edit::class, ['customer' => $customer])
        ->call('removeAadharFile')
        ->call('save')
        ->assertHasNoErrors();

    Storage::assertMissing($existingPath);
    $fresh = $customer->fresh();
    expect($fresh->aadhar_file_path)->toBeNull()
        ->and($fresh->aadhar_file_name)->toBeNull();
});

it('rejects unsupported file types', function () {
    Storage::fake();

    Livewire::test(Edit::class)
        ->set('first_name', 'A')
        ->set('business_type_id', $this->walking->id)
        ->set('phone', '9876543210')
        ->set('aadhar_file', UploadedFile::fake()->create('virus.exe', 100, 'application/octet-stream'))
        ->call('save')
        ->assertHasErrors(['aadhar_file']);
});

it('deleting a customer removes their KYC files from storage', function () {
    Storage::fake();
    $customer = CustomerMaster::factory()->create();
    $aadharPath = UploadedFile::fake()->image('a.jpg')->store("customers/{$customer->id}/aadhar");
    $panPath = UploadedFile::fake()->image('p.jpg')->store("customers/{$customer->id}/pan");
    $customer->forceFill([
        'aadhar_file_path' => $aadharPath, 'aadhar_file_name' => 'a.jpg',
        'pan_file_path' => $panPath, 'pan_file_name' => 'p.jpg',
    ])->save();

    $customer->delete();

    Storage::assertMissing($aadharPath);
    Storage::assertMissing($panPath);
});

it('quick-add Customer wizard creates a referrer and assigns it to referred_by_customer_id', function () {
    Livewire::test(Edit::class)
        ->set('quickCustomer.first_name', 'wizard')
        ->set('quickCustomer.phone', '9000111222')
        ->set('quickCustomer.business_type_id', $this->walking->id)
        ->call('createQuickCustomer')
        ->assertHasNoErrors();

    $newReferrer = CustomerMaster::where('phone', '9000111222')->firstOrFail();

    expect($newReferrer->first_name)->toBe('WIZARD')
        ->and($newReferrer->business_type_id)->toBe($this->walking->id);

    Livewire::test(Edit::class)
        ->set('quickCustomer.first_name', 'WIZARD2')
        ->set('quickCustomer.phone', '9000111223')
        ->set('quickCustomer.business_type_id', $this->walking->id)
        ->call('createQuickCustomer')
        ->assertSet('referred_by_customer_id', CustomerMaster::where('phone', '9000111223')->value('id'))
        ->assertSet('quickCustomer.first_name', '');
});

it('quick-add Customer wizard requires first_name + phone + business_type_id', function () {
    Livewire::test(Edit::class)
        ->call('createQuickCustomer')
        ->assertHasErrors([
            'quickCustomer.first_name',
            'quickCustomer.phone',
            'quickCustomer.business_type_id',
        ]);
});

it('rejects self-referral on edit', function () {
    $r = CustomerMaster::factory()->create();

    Livewire::test(Edit::class, ['customer' => $r])
        ->set('referred_by_customer_id', $r->id)
        ->call('save')
        ->assertHasErrors(['referred_by_customer_id']);
});

it('updates an existing customer', function () {
    $r = CustomerMaster::factory()->create(['name' => 'OLD NAME', 'business_type_id' => $this->walking->id]);

    Livewire::test(Edit::class, ['customer' => $r])
        ->set('first_name', 'updated')
        ->set('last_name', 'name')
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
        ->set('first_name', '')
        ->set('phone', '')
        ->call('save')
        ->assertHasErrors(['first_name' => 'required', 'phone' => 'required']);
});

it('validates PAN format', function () {
    Livewire::test(Edit::class)
        ->set('first_name', 'TEST')
        ->set('phone', '9876543210')
        ->set('pan', 'INVALID-PAN')
        ->call('save')
        ->assertHasErrors(['pan']);
});

it('validates aadhar must be exactly 12 chars if provided', function () {
    Livewire::test(Edit::class)
        ->set('first_name', 'TEST')
        ->set('phone', '9876543210')
        ->set('aadhar', '123')
        ->call('save')
        ->assertHasErrors(['aadhar']);
});

it('rejects duplicate aadhar', function () {
    CustomerMaster::factory()->create(['aadhar' => '111122223333']);

    Livewire::test(Edit::class)
        ->set('first_name', 'TEST')
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
