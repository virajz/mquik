<?php

use App\Models\User;
use App\Modules\CompanyMaster\Livewire\Index;
use App\Modules\CompanyMaster\Models\CompanyMaster;
use App\Support\SearchRegistry;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

it('renders the company page for an admin', function () {
    $this->actingAs(adminUser());

    $this->get(route('company-master.index'))
        ->assertOk()
        ->assertSeeLivewire(Index::class)
        ->assertSee('Company');
});

it('returns 403 for a user without company_master.view permission', function () {
    $this->actingAs(User::factory()->create());

    $this->get(route('company-master.index'))->assertForbidden();
});

it('saves a new company on first visit', function () {
    $this->actingAs(adminUser());

    Livewire::test(Index::class)
        ->set('legal_name', 'mquik auto services pvt ltd')
        ->set('trade_name', 'mquik workshop')
        ->set('code', 'mquik')
        ->call('save')
        ->assertHasNoErrors();

    expect(CompanyMaster::count())->toBe(1);
    $row = CompanyMaster::firstOrFail();
    expect($row->legal_name)->toBe('MQUIK AUTO SERVICES PVT LTD')
        ->and($row->trade_name)->toBe('MQUIK WORKSHOP')
        ->and($row->code)->toBe('MQUIK');
});

it('updates the existing company without creating a second row', function () {
    $this->actingAs(adminUser());

    $existing = CompanyMaster::create([
        'legal_name' => 'OLD LEGAL NAME',
        'trade_name' => 'OLD TRADE',
    ]);

    Livewire::test(Index::class)
        ->set('legal_name', 'updated legal name')
        ->call('save')
        ->assertHasNoErrors();

    expect(CompanyMaster::count())->toBe(1);
    expect($existing->fresh()->legal_name)->toBe('UPDATED LEGAL NAME');
});

it('validates required legal_name and trade_name', function () {
    $this->actingAs(adminUser());

    Livewire::test(Index::class)
        ->set('legal_name', '')
        ->set('trade_name', '')
        ->call('save')
        ->assertHasErrors(['legal_name' => 'required', 'trade_name' => 'required']);
});

it('blocks malformed GSTIN', function () {
    $this->actingAs(adminUser());

    Livewire::test(Index::class)
        ->set('legal_name', 'MQUIK PVT LTD')
        ->set('trade_name', 'MQUIK')
        ->set('gstin', 'NOT-A-VALID-GSTIN')
        ->call('save')
        ->assertHasErrors(['gstin']);
});

it('accepts a valid GSTIN', function () {
    $this->actingAs(adminUser());

    Livewire::test(Index::class)
        ->set('legal_name', 'MQUIK PVT LTD')
        ->set('trade_name', 'MQUIK')
        ->set('gstin', '24ABCDE1234F1Z5')
        ->call('save')
        ->assertHasNoErrors();

    expect(CompanyMaster::firstOrFail()->gstin)->toBe('24ABCDE1234F1Z5');
});

it('applies capital typing to legal_name and trade_name but not email/website/phone', function () {
    $this->actingAs(adminUser());

    Livewire::test(Index::class)
        ->set('legal_name', 'mquik auto services pvt ltd')
        ->set('trade_name', 'mquik workshop')
        ->set('email', 'admin@mquik.com')
        ->set('website', 'https://mquik.com')
        ->set('phone', '9876543210')
        ->call('save')
        ->assertHasNoErrors();

    $row = CompanyMaster::firstOrFail();
    expect($row->legal_name)->toBe('MQUIK AUTO SERVICES PVT LTD')
        ->and($row->trade_name)->toBe('MQUIK WORKSHOP')
        ->and($row->email)->toBe('admin@mquik.com')
        ->and($row->website)->toBe('https://mquik.com')
        ->and($row->phone)->toBe('9876543210');
});

it('stores a logo upload and sets logo_path', function () {
    $this->actingAs(adminUser());
    Storage::fake('public');

    $logo = UploadedFile::fake()->image('logo.png', 200, 80);

    Livewire::test(Index::class)
        ->set('legal_name', 'MQUIK PVT LTD')
        ->set('trade_name', 'MQUIK')
        ->set('logo', $logo)
        ->call('save')
        ->assertHasNoErrors();

    $row = CompanyMaster::firstOrFail();
    expect($row->logo_path)->not->toBeNull();
    expect(str_starts_with($row->logo_path, 'companies/logos/'))->toBeTrue();
    Storage::disk('public')->assertExists($row->logo_path);
});

it('exposes the company through Master Search', function () {
    $admin = adminUser();
    $this->actingAs($admin);

    CompanyMaster::create([
        'legal_name' => 'MQUIK AUTO SERVICES PVT LTD',
        'trade_name' => 'MQUIK WORKSHOP',
        'gstin' => '24ABCDE1234F1Z5',
    ]);

    $results = app(SearchRegistry::class)->search('MQUIK');

    expect($results)->not->toBeEmpty();

    $companySection = collect($results)->firstWhere('module', 'CompanyMaster');
    expect($companySection)->not->toBeNull();
    expect($companySection['rows'][0]['title'])->toBe('MQUIK AUTO SERVICES PVT LTD');
});
