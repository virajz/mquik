<?php

use App\Models\User;
use App\Modules\PurchaseReport\Livewire\Index;
use App\Modules\VendorMaster\Models\VendorMaster;
use App\Modules\VendorPurchaseInquiry\Models\VendorPurchaseInquiry;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(adminUser());
});

it('renders the report page', function () {
    VendorPurchaseInquiry::factory()->count(3)->create();

    $this->get(route('purchase-report.index'))
        ->assertOk()
        ->assertSeeLivewire(Index::class);
});

it('filters by status', function () {
    $done = VendorPurchaseInquiry::factory()->completed()->create();
    $pending = VendorPurchaseInquiry::factory()->create();

    Livewire::test(Index::class)
        ->set('statusFilter', VendorPurchaseInquiry::STATUS_COMPLETED)
        ->assertSee($done->vpi_no)
        ->assertDontSee($pending->vpi_no);
});

it('filters by vendor', function () {
    $vendor = VendorMaster::factory()->create();
    $mine = VendorPurchaseInquiry::factory()->create(['vendor_id' => $vendor->id]);
    $other = VendorPurchaseInquiry::factory()->create();

    Livewire::test(Index::class)
        ->set('vendorFilter', (string) $vendor->id)
        ->assertSee($mine->vpi_no)
        ->assertDontSee($other->vpi_no);
});

it('exports the filtered set as CSV', function () {
    VendorPurchaseInquiry::factory()->completed()->create();

    Livewire::test(Index::class)
        ->set('statusFilter', VendorPurchaseInquiry::STATUS_COMPLETED)
        ->call('download')
        ->assertFileDownloaded();
});

it('blocks export without permission', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('purchase_report.view');
    $this->actingAs($user);

    Livewire::test(Index::class)
        ->call('download')
        ->assertForbidden();
});

it('requires authentication', function () {
    auth()->logout();
    $this->get(route('purchase-report.index'))->assertRedirect(route('login'));
});
