<?php

use App\Modules\VendorMaster\Livewire\Form;
use App\Modules\VendorMaster\Livewire\Index;
use App\Modules\VendorMaster\Models\VendorMaster;
use App\Modules\VendorTypeMaster\Models\VendorTypeMaster;
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

it('creates a vendor with full details', function () {
    Livewire::test(Form::class)
        ->set('vendor_code', 'VND-00001')
        ->set('name', 'bosch dealer')
        ->set('vendor_type_id', $this->sparesType->id)
        ->set('phone', '9876543210')
        ->set('email', 'vendor@example.com')
        ->set('credit_days', 45)
        ->set('credit_limit', 75000.50)
        ->call('save')
        ->assertHasNoErrors()
        ->assertDispatched('vendor-master:saved');

    $r = VendorMaster::firstOrFail();
    expect($r->name)->toBe('BOSCH DEALER')              // capital typing
        ->and($r->vendor_code)->toBe('VND-00001')
        ->and($r->email)->toBe('vendor@example.com')    // email skipped from caps
        ->and($r->vendor_type_id)->toBe($this->sparesType->id)
        ->and((int) $r->credit_days)->toBe(45)
        ->and((float) $r->credit_limit)->toBe(75000.50);
});

it('requires vendor_code, name, phone and vendor_type_id', function () {
    Livewire::test(Form::class)
        ->call('save')
        ->assertHasErrors(['vendor_code', 'name', 'phone', 'vendor_type_id']);
});

it('rejects invalid email format', function () {
    Livewire::test(Form::class)
        ->set('vendor_code', 'VND-X')
        ->set('name', 'TEST')
        ->set('vendor_type_id', $this->sparesType->id)
        ->set('phone', '9999999999')
        ->set('email', 'not-an-email')
        ->call('save')
        ->assertHasErrors(['email']);
});

it('blocks duplicate vendor_code', function () {
    VendorMaster::factory()->create(['vendor_code' => 'VND-DUPE']);

    Livewire::test(Form::class)
        ->set('vendor_code', 'VND-DUPE')
        ->set('name', 'TEST')
        ->set('vendor_type_id', $this->sparesType->id)
        ->set('phone', '9999999999')
        ->call('save')
        ->assertHasErrors(['vendor_code']);
});

it('blocks duplicate gstin (with valid format)', function () {
    VendorMaster::factory()->create([
        'vendor_code' => 'VND-G1',
        'gstin' => '24ABCDE1234F1Z5',
    ]);

    Livewire::test(Form::class)
        ->set('vendor_code', 'VND-G2')
        ->set('name', 'TEST')
        ->set('vendor_type_id', $this->sparesType->id)
        ->set('phone', '9999999999')
        ->set('gstin', '24ABCDE1234F1Z5')
        ->call('save')
        ->assertHasErrors(['gstin']);
});

it('rejects invalid gstin pattern', function () {
    Livewire::test(Form::class)
        ->set('vendor_code', 'VND-X')
        ->set('name', 'TEST')
        ->set('vendor_type_id', $this->sparesType->id)
        ->set('phone', '9999999999')
        ->set('gstin', 'NOTAGSTIN123456')
        ->call('save')
        ->assertHasErrors(['gstin']);
});

it('rejects unknown vendor_type_id', function () {
    Livewire::test(Form::class)
        ->set('vendor_code', 'VND-X')
        ->set('name', 'TEST')
        ->set('vendor_type_id', 99999)
        ->set('phone', '9999999999')
        ->call('save')
        ->assertHasErrors(['vendor_type_id']);
});

it('allows nullable kyc and banking fields', function () {
    Livewire::test(Form::class)
        ->set('vendor_code', 'VND-00099')
        ->set('name', 'MINIMAL VENDOR')
        ->set('vendor_type_id', $this->sparesType->id)
        ->set('phone', '9999999999')
        ->call('save')
        ->assertHasNoErrors();

    $r = VendorMaster::where('vendor_code', 'VND-00099')->firstOrFail();
    expect($r->pan)->toBeNull()
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
    VendorMaster::factory()->create(['name' => 'SPARE VENDOR ONE', 'vendor_type_id' => $this->sparesType->id]);
    VendorMaster::factory()->create(['name' => 'OSL VENDOR ONE', 'vendor_type_id' => $this->oslType->id]);
    VendorMaster::factory()->inactive()->create(['name' => 'GHOST VENDOR', 'vendor_type_id' => $this->sparesType->id]);

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

    Livewire::test(Form::class)
        ->dispatch('vendor-master:edit', id: $r->id)
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

it('requires authentication', function () {
    auth()->logout();

    $this->get(route('vendor-master.index'))->assertRedirect(route('login'));
});
