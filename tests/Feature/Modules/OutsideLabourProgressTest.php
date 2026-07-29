<?php

use App\Models\User;
use App\Modules\OutsideLabourOrder\Models\OutsideLabourOrder;
use App\Modules\OutsideLabourProgress\Livewire\Index;
use App\Modules\VendorMaster\Models\VendorMaster;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(adminUser());
});

it('renders the report page', function () {
    OutsideLabourOrder::factory()->count(3)->create();

    $this->get(route('outside-labour-progress.index'))
        ->assertOk()
        ->assertSeeLivewire(Index::class);
});

it('filters by status', function () {
    $done = OutsideLabourOrder::factory()->completed()->create();
    $pending = OutsideLabourOrder::factory()->create();

    Livewire::test(Index::class)
        ->set('statusFilter', OutsideLabourOrder::STATUS_COMPLETED)
        ->assertSee($done->order_no)
        ->assertDontSee($pending->order_no);
});

it('filters by vendor', function () {
    $vendor = VendorMaster::factory()->create();
    $mine = OutsideLabourOrder::factory()->create(['vendor_id' => $vendor->id]);
    $other = OutsideLabourOrder::factory()->create();

    Livewire::test(Index::class)
        ->set('vendorFilter', (string) $vendor->id)
        ->assertSee($mine->order_no)
        ->assertDontSee($other->order_no);
});

it('exports the status report as CSV', function () {
    OutsideLabourOrder::factory()->completed()->create();

    Livewire::test(Index::class)
        ->call('download')
        ->assertFileDownloaded();
});

it('blocks export without permission', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('outside_labour_progress.view');
    $this->actingAs($user);

    Livewire::test(Index::class)
        ->call('download')
        ->assertForbidden();
});

it('requires authentication', function () {
    auth()->logout();
    $this->get(route('outside-labour-progress.index'))->assertRedirect(route('login'));
});
