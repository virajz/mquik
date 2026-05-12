<?php

use App\Modules\BankMaster\Models\BankMaster;
use App\Modules\RegionMaster\Models\RegionMaster;
use App\Modules\VendorMaster\Livewire\Edit;
use App\Modules\VendorMaster\Livewire\Index;
use App\Modules\VendorMaster\Models\VendorMaster;
use App\Modules\VendorMaster\Models\VendorTerm;
use App\Modules\VendorTypeMaster\Models\VendorTypeMaster;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(adminUser());
    $this->sparesType = VendorTypeMaster::firstOrCreate(['name' => 'SPARE PARTS'], ['is_active' => true, 'code' => 'SP']);
    $this->oslType = VendorTypeMaster::firstOrCreate(['name' => 'OSL'], ['is_active' => true, 'code' => 'OSL']);
});

it('renders the index page', function () {
    VendorMaster::factory()->count(3)->create();
    $this->get(route('vendor-master.index'))->assertOk()->assertSeeLivewire(Index::class);
});

it('renders the create page', function () {
    $this->get(route('vendor-master.create'))
        ->assertOk()
        ->assertSeeLivewire(Edit::class)
        ->assertSee('New Vendor');
});

it('renders the edit page for an existing vendor', function () {
    $vendor = VendorMaster::factory()->create(['name' => 'BOSCH DEALER']);

    $this->get(route('vendor-master.edit', $vendor))
        ->assertOk()
        ->assertSeeLivewire(Edit::class)
        ->assertSee('BOSCH DEALER');
});

it('creates a vendor with full details', function () {
    Livewire::test(Edit::class)
        ->set('vendor_code', 'VND-00001')
        ->set('name', 'bosch dealer')
        ->set('vendor_type_ids', [$this->sparesType->id])
        ->set('phone', '9876543210')
        ->set('email', 'vendor@example.com')
        ->set('credit_days', 45)
        ->set('credit_limit', 75000.50)
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect(route('vendor-master.index'));

    $r = VendorMaster::with('vendorTypes')->firstOrFail();
    expect($r->name)->toBe('BOSCH DEALER')
        ->and($r->vendor_code)->toBe('VND-00001')
        ->and($r->email)->toBe('vendor@example.com')
        ->and($r->vendorTypes->pluck('id')->all())->toBe([$this->sparesType->id])
        ->and((int) $r->credit_days)->toBe(45)
        ->and((float) $r->credit_limit)->toBe(75000.50);
});

it('allows multiple vendor types per vendor', function () {
    Livewire::test(Edit::class)
        ->set('vendor_code', 'VND-MULTI')
        ->set('name', 'MULTI VENDOR')
        ->set('vendor_type_ids', [$this->sparesType->id, $this->oslType->id])
        ->set('phone', '9876543210')
        ->call('save')
        ->assertHasNoErrors();

    $r = VendorMaster::with('vendorTypes')->where('vendor_code', 'VND-MULTI')->firstOrFail();
    expect($r->vendorTypes->pluck('id')->all())
        ->toEqualCanonicalizing([$this->sparesType->id, $this->oslType->id]);
});

it('requires at least one vendor type', function () {
    Livewire::test(Edit::class)
        ->set('vendor_code', 'VND-NOTYPE')
        ->set('name', 'NO TYPE')
        ->set('vendor_type_ids', [])
        ->set('phone', '9999999999')
        ->call('save')
        ->assertHasErrors(['vendor_type_ids']);
});

it('requires vendor_code, name, phone and vendor_type_ids', function () {
    Livewire::test(Edit::class)
        ->call('save')
        ->assertHasErrors(['vendor_code', 'name', 'phone', 'vendor_type_ids']);
});

it('rejects invalid email format', function () {
    Livewire::test(Edit::class)
        ->set('vendor_code', 'VND-X')
        ->set('name', 'TEST')
        ->set('vendor_type_ids', [$this->sparesType->id])
        ->set('phone', '9999999999')
        ->set('email', 'not-an-email')
        ->call('save')
        ->assertHasErrors(['email']);
});

it('saves a secondary email and rejects it being identical to the primary', function () {
    Livewire::test(Edit::class)
        ->set('vendor_code', 'VND-SE-1')
        ->set('name', 'TEST')
        ->set('vendor_type_ids', [$this->sparesType->id])
        ->set('phone', '9999999999')
        ->set('email', 'primary@vendor.test')
        ->set('secondary_email', 'primary@vendor.test')
        ->call('save')
        ->assertHasErrors(['secondary_email']);

    Livewire::test(Edit::class)
        ->set('vendor_code', 'VND-SE-2')
        ->set('name', 'TEST')
        ->set('vendor_type_ids', [$this->sparesType->id])
        ->set('phone', '9999999998')
        ->set('email', 'primary@vendor.test')
        ->set('secondary_email', 'backup@vendor.test')
        ->call('save')
        ->assertHasNoErrors();

    expect(VendorMaster::where('vendor_code', 'VND-SE-2')->firstOrFail()->secondary_email)
        ->toBe('backup@vendor.test');
});

it('blocks duplicate vendor_code', function () {
    VendorMaster::factory()->create(['vendor_code' => 'VND-DUPE']);

    Livewire::test(Edit::class)
        ->set('vendor_code', 'VND-DUPE')
        ->set('name', 'TEST')
        ->set('vendor_type_ids', [$this->sparesType->id])
        ->set('phone', '9999999999')
        ->call('save')
        ->assertHasErrors(['vendor_code']);
});

it('blocks duplicate gstin (with valid format)', function () {
    VendorMaster::factory()->create([
        'vendor_code' => 'VND-G1',
        'gstin' => '24ABCDE1234F1Z5',
    ]);

    Livewire::test(Edit::class)
        ->set('vendor_code', 'VND-G2')
        ->set('name', 'TEST')
        ->set('vendor_type_ids', [$this->sparesType->id])
        ->set('phone', '9999999999')
        ->set('gstin', '24ABCDE1234F1Z5')
        ->call('save')
        ->assertHasErrors(['gstin']);
});

it('rejects invalid gstin pattern', function () {
    Livewire::test(Edit::class)
        ->set('vendor_code', 'VND-X')
        ->set('name', 'TEST')
        ->set('vendor_type_ids', [$this->sparesType->id])
        ->set('phone', '9999999999')
        ->set('gstin', 'NOTAGSTIN123456')
        ->call('save')
        ->assertHasErrors(['gstin']);
});

it('rejects unknown vendor_type_ids', function () {
    Livewire::test(Edit::class)
        ->set('vendor_code', 'VND-X')
        ->set('name', 'TEST')
        ->set('vendor_type_ids', [99999])
        ->set('phone', '9999999999')
        ->call('save')
        ->assertHasErrors(['vendor_type_ids.0']);
});

it('allows nullable kyc and banking fields', function () {
    Livewire::test(Edit::class)
        ->set('vendor_code', 'VND-00099')
        ->set('name', 'MINIMAL VENDOR')
        ->set('vendor_type_ids', [$this->sparesType->id])
        ->set('phone', '9999999999')
        ->call('save')
        ->assertHasNoErrors();

    $r = VendorMaster::where('vendor_code', 'VND-00099')->firstOrFail();
    expect($r->aadhar)->toBeNull()
        ->and($r->pan)->toBeNull()
        ->and($r->gstin)->toBeNull()
        ->and($r->bank_name)->toBeNull()
        ->and($r->ifsc)->toBeNull();
});

it('searches by name, code, phone, and gstin', function () {
    VendorMaster::factory()->create(['vendor_code' => 'VND-AAAA', 'name' => 'ALPHA TRADERS', 'phone' => '9111111111', 'gstin' => '24AAAAA1234F1Z1']);
    VendorMaster::factory()->create(['vendor_code' => 'VND-BBBB', 'name' => 'BETA SUPPLIERS', 'phone' => '9222222222', 'gstin' => '24BBBBB5678G1Z2']);

    Livewire::test(Index::class)->set('search', 'alpha')
        ->assertSee('ALPHA TRADERS')
        ->assertDontSee('BETA SUPPLIERS');

    Livewire::test(Index::class)->set('search', 'VND-BBBB')
        ->assertSee('BETA SUPPLIERS')
        ->assertDontSee('ALPHA TRADERS');

    Livewire::test(Index::class)->set('search', '24AAAAA')
        ->assertSee('ALPHA TRADERS')
        ->assertDontSee('BETA SUPPLIERS');
});

it('filters by vendor type and status', function () {
    VendorMaster::factory()->withTypes($this->sparesType)->create(['name' => 'SPARE VENDOR ONE']);
    VendorMaster::factory()->withTypes($this->oslType)->create(['name' => 'OSL VENDOR ONE']);
    VendorMaster::factory()->withTypes($this->sparesType)->inactive()->create(['name' => 'GHOST VENDOR']);

    Livewire::test(Index::class)->set('typeFilter', (string) $this->sparesType->id)
        ->assertSee('SPARE VENDOR ONE')
        ->assertSee('GHOST VENDOR')
        ->assertDontSee('OSL VENDOR ONE');

    Livewire::test(Index::class)->set('statusFilter', 'inactive')
        ->assertSee('GHOST VENDOR')
        ->assertDontSee('SPARE VENDOR ONE')
        ->assertDontSee('OSL VENDOR ONE');
});

it('sorts by name ascending by default', function () {
    VendorMaster::factory()->create(['name' => 'ZEBRA TRADERS']);
    VendorMaster::factory()->create(['name' => 'ALPHA TRADERS']);
    VendorMaster::factory()->create(['name' => 'MIDDLE TRADERS']);

    $html = Livewire::test(Index::class)->html();

    expect(strpos($html, 'ALPHA TRADERS'))->toBeLessThan(strpos($html, 'MIDDLE TRADERS'))
        ->and(strpos($html, 'MIDDLE TRADERS'))->toBeLessThan(strpos($html, 'ZEBRA TRADERS'));
});

it('updates a vendor', function () {
    $r = VendorMaster::factory()->create(['name' => 'OLD NAME']);

    Livewire::test(Edit::class, ['vendor' => $r])
        ->set('name', 'updated name')
        ->call('save')
        ->assertHasNoErrors();

    expect($r->fresh()->name)->toBe('UPDATED NAME');
});

it('deletes a vendor', function () {
    $r = VendorMaster::factory()->create();

    Livewire::test(Index::class)->call('delete', $r->id);

    expect(VendorMaster::find($r->id))->toBeNull();
});

it('create-option: creates a new vendor type from the multi-picker and appends it', function () {
    Livewire::test(Edit::class)
        ->set('vendor_type_ids', [$this->sparesType->id])
        ->set('vendorTypeSearch', 'fleet services')
        ->call('createVendorType')
        ->assertHasNoErrors();

    $newType = VendorTypeMaster::where('name', 'FLEET SERVICES')->firstOrFail();

    Livewire::test(Edit::class)
        ->set('vendor_type_ids', [$this->sparesType->id])
        ->set('vendorTypeSearch', 'fleet services')
        ->call('createVendorType')
        ->assertSet('vendor_type_ids', [$this->sparesType->id, $newType->id])
        ->assertSet('vendorTypeSearch', '');
});

it('create-option: re-typing an existing type name does not duplicate the selection', function () {
    Livewire::test(Edit::class)
        ->set('vendor_type_ids', [$this->sparesType->id])
        ->set('vendorTypeSearch', 'spare parts')
        ->call('createVendorType')
        ->assertSet('vendor_type_ids', [$this->sparesType->id]);

    expect(VendorTypeMaster::where('name', 'SPARE PARTS')->count())->toBe(1);
});

it('create-option: empty search is a no-op', function () {
    $countBefore = VendorTypeMaster::count();

    Livewire::test(Edit::class)
        ->set('vendorTypeSearch', '   ')
        ->call('createVendorType')
        ->assertSet('vendor_type_ids', []);

    expect(VendorTypeMaster::count())->toBe($countBefore);
});

it('saves region_id picked from RegionMaster', function () {
    $state = RegionMaster::firstOrCreate(['kind' => 'state', 'parent_id' => null, 'name' => 'GUJARAT'], ['code' => 'GJ', 'is_active' => true]);
    $city = RegionMaster::firstOrCreate(['kind' => 'city', 'parent_id' => $state->id, 'name' => 'AHMEDABAD'], ['code' => 'AHD', 'is_active' => true]);

    Livewire::test(Edit::class)
        ->set('vendor_code', 'VND-RG1')
        ->set('name', 'GUJARAT VENDOR')
        ->set('vendor_type_ids', [$this->sparesType->id])
        ->set('phone', '9876543210')
        ->set('region_id', $city->id)
        ->call('save')
        ->assertHasNoErrors();

    expect(VendorMaster::where('vendor_code', 'VND-RG1')->firstOrFail()->region_id)->toBe($city->id);
});

it('create-option: creates a new region of the chosen kind for the vendor address', function () {
    $component = Livewire::test(Edit::class)
        ->set('regionSearch', '380058')
        ->call('createRegion', 'pincode')
        ->assertHasNoErrors();

    $region = RegionMaster::where('kind', 'pincode')->where('name', '380058')->firstOrFail();

    $component
        ->assertSet('region_id', $region->id)
        ->assertSet('regionSearch', '');
});

it('create-option: re-typing an existing kind+name selects existing region, no duplicate', function () {
    $existing = RegionMaster::create(['kind' => 'city', 'name' => 'AHMEDABAD', 'parent_id' => null, 'is_active' => true]);

    Livewire::test(Edit::class)
        ->set('regionSearch', 'ahmedabad')
        ->call('createRegion', 'city')
        ->assertSet('region_id', $existing->id);

    expect(RegionMaster::where('kind', 'city')->where('name', 'AHMEDABAD')->where('parent_id', null)->count())->toBe(1);
});

it('create-option region: invalid kind is a no-op', function () {
    $countBefore = RegionMaster::count();

    Livewire::test(Edit::class)
        ->set('regionSearch', 'BOGUS')
        ->call('createRegion', 'galaxy')
        ->assertSet('region_id', null);

    expect(RegionMaster::count())->toBe($countBefore);
});

it('saves bank_id picked from BankMaster', function () {
    $bank = BankMaster::firstOrCreate(['name' => 'HDFC BANK'], ['is_active' => true]);

    Livewire::test(Edit::class)
        ->set('vendor_code', 'VND-BK1')
        ->set('name', 'BANKED VENDOR')
        ->set('vendor_type_ids', [$this->sparesType->id])
        ->set('phone', '9876543210')
        ->set('bank_id', $bank->id)
        ->call('save')
        ->assertHasNoErrors();

    expect(VendorMaster::where('vendor_code', 'VND-BK1')->firstOrFail()->bank_id)->toBe($bank->id);
});

it('create-option: creates a new bank from the picker and selects it', function () {
    Livewire::test(Edit::class)
        ->set('bankSearch', 'kotak mahindra bank')
        ->call('createBank')
        ->assertHasNoErrors();

    $bank = BankMaster::where('name', 'KOTAK MAHINDRA BANK')->firstOrFail();

    Livewire::test(Edit::class)
        ->set('bankSearch', 'kotak mahindra bank')
        ->call('createBank')
        ->assertSet('bank_id', $bank->id)
        ->assertSet('bankSearch', '');
});

it('create-option: re-typing an existing bank name selects existing, no duplicate', function () {
    $existing = BankMaster::firstOrCreate(['name' => 'HDFC BANK'], ['is_active' => true]);

    Livewire::test(Edit::class)
        ->set('bankSearch', 'hdfc bank')
        ->call('createBank')
        ->assertSet('bank_id', $existing->id);

    expect(BankMaster::where('name', 'HDFC BANK')->count())->toBe(1);
});

it('saves multiple terms with names and values', function () {
    Livewire::test(Edit::class)
        ->set('vendor_code', 'VND-T1')
        ->set('name', 'TERMS VENDOR')
        ->set('vendor_type_ids', [$this->sparesType->id])
        ->set('phone', '9999999999')
        ->set('terms', [
            ['id' => null, 'name' => 'Payment Term', 'value' => 'Net 30 days'],
            ['id' => null, 'name' => 'Delivery Term', 'value' => 'FOB Mumbai'],
            ['id' => null, 'name' => 'Warranty', 'value' => '1 year on parts'],
        ])
        ->call('save')
        ->assertHasNoErrors();

    $vendor = VendorMaster::with('terms')->where('vendor_code', 'VND-T1')->firstOrFail();
    expect($vendor->terms)->toHaveCount(3)
        ->and($vendor->terms->pluck('name')->all())->toBe(['Payment Term', 'Delivery Term', 'Warranty'])
        ->and($vendor->terms->pluck('value')->all())->toBe(['Net 30 days', 'FOB Mumbai', '1 year on parts']);
});

it('drops blank term rows on save', function () {
    Livewire::test(Edit::class)
        ->set('vendor_code', 'VND-T2')
        ->set('name', 'TERMS VENDOR')
        ->set('vendor_type_ids', [$this->sparesType->id])
        ->set('phone', '9999999999')
        ->set('terms', [
            ['id' => null, 'name' => 'Payment Term', 'value' => 'Net 30'],
            ['id' => null, 'name' => '', 'value' => ''],
            ['id' => null, 'name' => '', 'value' => ''],
        ])
        ->call('save')
        ->assertHasNoErrors();

    $vendor = VendorMaster::with('terms')->where('vendor_code', 'VND-T2')->firstOrFail();
    expect($vendor->terms)->toHaveCount(1);
});

it('term name is required when value is provided (and vice versa)', function () {
    Livewire::test(Edit::class)
        ->set('vendor_code', 'VND-T3')
        ->set('name', 'TERMS VENDOR')
        ->set('vendor_type_ids', [$this->sparesType->id])
        ->set('phone', '9999999999')
        ->set('terms', [
            ['id' => null, 'name' => '', 'value' => 'Net 30'],
        ])
        ->call('save')
        ->assertHasErrors(['terms.0.name']);
});

it('replaces removed terms on edit', function () {
    $vendor = VendorMaster::factory()->create();
    $a = VendorTerm::factory()->create(['vendor_id' => $vendor->id, 'name' => 'A', 'value' => 'a-val']);
    $b = VendorTerm::factory()->create(['vendor_id' => $vendor->id, 'name' => 'B', 'value' => 'b-val']);

    Livewire::test(Edit::class, ['vendor' => $vendor])
        ->set('terms', [
            ['id' => $a->id, 'name' => 'A-UPDATED', 'value' => 'a-new'],
        ])
        ->call('save')
        ->assertHasNoErrors();

    expect(VendorTerm::find($b->id))->toBeNull()
        ->and(VendorTerm::find($a->id)->name)->toBe('A-UPDATED');
});

it('uploads aadhar file, persists path + original filename, can stream download', function () {
    Storage::fake();

    $vendor = VendorMaster::factory()->create();

    Livewire::test(Edit::class, ['vendor' => $vendor])
        ->set('aadhar_file', UploadedFile::fake()->image('Vendor Aadhar.jpg'))
        ->call('save')
        ->assertHasNoErrors();

    $fresh = $vendor->fresh();
    expect($fresh->aadhar_file_path)->toStartWith("vendors/{$vendor->id}/aadhar/")
        ->and($fresh->aadhar_file_name)->toBe('Vendor Aadhar.jpg');

    Storage::assertExists($fresh->aadhar_file_path);

    $response = $this->get(route('vendor-master.file', ['vendor' => $vendor, 'type' => 'aadhar']))
        ->assertOk();
    expect($response->headers->get('content-disposition'))->toContain('Vendor Aadhar.jpg');
});

it('replacing a pan file deletes the old one from storage', function () {
    Storage::fake();
    $vendor = VendorMaster::factory()->create();
    $oldPath = UploadedFile::fake()->image('old.jpg')->store("vendors/{$vendor->id}/pan");
    $vendor->forceFill(['pan_file_path' => $oldPath, 'pan_file_name' => 'old.jpg'])->save();

    Livewire::test(Edit::class, ['vendor' => $vendor])
        ->set('pan_file', UploadedFile::fake()->image('new.jpg'))
        ->call('save')
        ->assertHasNoErrors();

    Storage::assertMissing($oldPath);
    expect($vendor->fresh()->pan_file_name)->toBe('new.jpg');
});

it('removing an existing aadhar file wipes path + name + storage on save', function () {
    Storage::fake();
    $vendor = VendorMaster::factory()->create();
    $existingPath = UploadedFile::fake()->image('keep.jpg')->store("vendors/{$vendor->id}/aadhar");
    $vendor->forceFill(['aadhar_file_path' => $existingPath, 'aadhar_file_name' => 'keep.jpg'])->save();

    Livewire::test(Edit::class, ['vendor' => $vendor])
        ->call('removeAadharFile')
        ->call('save')
        ->assertHasNoErrors();

    Storage::assertMissing($existingPath);
    $fresh = $vendor->fresh();
    expect($fresh->aadhar_file_path)->toBeNull()
        ->and($fresh->aadhar_file_name)->toBeNull();
});

it('rejects unsupported KYC file types', function () {
    Storage::fake();
    $vendor = VendorMaster::factory()->create();

    Livewire::test(Edit::class, ['vendor' => $vendor])
        ->set('aadhar_file', UploadedFile::fake()->create('virus.exe', 100, 'application/octet-stream'))
        ->call('save')
        ->assertHasErrors(['aadhar_file']);
});

it('deleting a vendor removes their KYC files from storage', function () {
    Storage::fake();
    $vendor = VendorMaster::factory()->create();
    $aadharPath = UploadedFile::fake()->image('a.jpg')->store("vendors/{$vendor->id}/aadhar");
    $panPath = UploadedFile::fake()->image('p.jpg')->store("vendors/{$vendor->id}/pan");
    $vendor->forceFill([
        'aadhar_file_path' => $aadharPath, 'aadhar_file_name' => 'a.jpg',
        'pan_file_path' => $panPath, 'pan_file_name' => 'p.jpg',
    ])->save();

    $vendor->delete();

    Storage::assertMissing($aadharPath);
    Storage::assertMissing($panPath);
});

it('requires authentication', function () {
    auth()->logout();

    $this->get(route('vendor-master.index'))->assertRedirect(route('login'));
});
