<?php

use App\Models\User;
use App\Modules\OutsideLabourInquiry\Models\OutsideLabourInquiry;
use App\Modules\OutsideLabourStatusReport\Livewire\Index;
use App\Modules\VendorMaster\Models\VendorMaster;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(adminUser());
});

it('renders the report page', function () {
    OutsideLabourInquiry::factory()->count(3)->create();

    $this->get(route('outside-labour-status-report.index'))
        ->assertOk()
        ->assertSeeLivewire(Index::class);
});

it('filters by status', function () {
    $issued = OutsideLabourInquiry::factory()->issued()->create();
    $pending = OutsideLabourInquiry::factory()->create();

    Livewire::test(Index::class)
        ->set('statusFilter', OutsideLabourInquiry::STATUS_WORK_ORDER_ISSUED)
        ->assertSee($issued->inquiry_no)
        ->assertDontSee($pending->inquiry_no);
});

it('filters by vendor', function () {
    $vendor = VendorMaster::factory()->create();
    $mine = OutsideLabourInquiry::factory()->create(['vendor_id' => $vendor->id]);
    $other = OutsideLabourInquiry::factory()->create();

    Livewire::test(Index::class)
        ->set('vendorFilter', (string) $vendor->id)
        ->assertSee($mine->inquiry_no)
        ->assertDontSee($other->inquiry_no);
});

it('exports the filtered set as CSV', function () {
    OutsideLabourInquiry::factory()->issued()->create();

    Livewire::test(Index::class)
        ->set('statusFilter', OutsideLabourInquiry::STATUS_WORK_ORDER_ISSUED)
        ->call('download')
        ->assertFileDownloaded();
});

it('blocks export without permission', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('outside_labour_status_report.view');
    $this->actingAs($user);

    Livewire::test(Index::class)
        ->call('download')
        ->assertForbidden();
});

it('requires authentication', function () {
    auth()->logout();
    $this->get(route('outside-labour-status-report.index'))->assertRedirect(route('login'));
});
