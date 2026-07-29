<?php

use App\Models\User;
use App\Modules\SpareMaster\Models\SpareMaster;
use App\Modules\VendorMaster\Models\VendorMaster;
use App\Modules\VpoApproval\Livewire\Edit;
use App\Modules\VpoApproval\Livewire\Index;
use App\Modules\VpoApproval\Models\VpoApproval;
use App\Modules\VpoApproval\Models\VpoApprovalItem;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(adminUser());
});

it('renders the index page', function () {
    VpoApproval::factory()->count(3)->create();

    $this->get(route('vpo-approval.index'))->assertOk()->assertSeeLivewire(Index::class);
});

it('renders the create page', function () {
    $this->get(route('vpo-approval.create'))->assertOk()->assertSeeLivewire(Edit::class);
});

it('creates an approval with a per-line approval and redirects', function () {
    $vendor = VendorMaster::factory()->create();
    $spare = SpareMaster::factory()->create();

    Livewire::test(Edit::class)
        ->set('po_approval_type', 'stock_bulk')
        ->set('vendor_id', $vendor->id)
        ->set('items.0.spare_id', $spare->id)
        ->set('items.0.description', 'brake pad')
        ->set('items.0.quantity', 10)
        ->set('items.0.qty_approved', 8)
        ->set('items.0.rate_approved', 250)
        ->set('items.0.part_approved', true)
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect(route('vpo-approval.index'));

    $a = VpoApproval::with('items')->first();
    expect($a->approval_no)->toBe('VPA-'.str_pad((string) $a->id, 5, '0', STR_PAD_LEFT))
        ->and($a->vendor_id)->toBe($vendor->id);
    $item = $a->items->first();
    expect((float) $item->qty_approved)->toBe(8.0)
        ->and((float) $item->rate_approved)->toBe(250.0)
        ->and($item->part_approved)->toBeTrue();
});

it('requires a vendor', function () {
    Livewire::test(Edit::class)
        ->set('vendor_id', null)
        ->set('items.0.description', 'x')
        ->call('save')
        ->assertHasErrors(['vendor_id']);
});

it('requires a job card for odd-item / high-value purchases', function () {
    $vendor = VendorMaster::factory()->create();

    Livewire::test(Edit::class)
        ->set('po_approval_type', 'high_value')
        ->set('vendor_id', $vendor->id)
        ->set('items.0.description', 'x')
        ->call('save')
        ->assertHasErrors(['job_card_id']);
});

it('stamps approved_at when fully approved', function () {
    $a = VpoApproval::factory()->create();

    Livewire::test(Edit::class, ['vpoApproval' => $a])
        ->set('items.0.description', 'x')
        ->set('status', VpoApproval::STATUS_FULLY_APPROVED)
        ->call('save')
        ->assertHasNoErrors();

    expect($a->fresh()->approved_at)->not->toBeNull();
});

it('requires a rejection reason when rejected', function () {
    $vendor = VendorMaster::factory()->create();

    Livewire::test(Edit::class)
        ->set('vendor_id', $vendor->id)
        ->set('items.0.description', 'x')
        ->set('status', VpoApproval::STATUS_REJECTED)
        ->call('save')
        ->assertHasErrors(['rejection_reason']);
});

it('filters by status and type', function () {
    $approved = VpoApproval::factory()->fullyApproved()->create(['po_approval_type' => 'emergency']);
    $sent = VpoApproval::factory()->create(['po_approval_type' => 'stock_bulk']);

    Livewire::test(Index::class)
        ->set('statusFilter', VpoApproval::STATUS_FULLY_APPROVED)
        ->assertSee($approved->approval_no)->assertDontSee($sent->approval_no);

    Livewire::test(Index::class)
        ->set('typeFilter', 'emergency')
        ->assertSee($approved->approval_no)->assertDontSee($sent->approval_no);
});

it('exports the purchase report as CSV', function () {
    VpoApproval::factory()->fullyApproved()->create();

    Livewire::test(Index::class)->call('download')->assertFileDownloaded();
});

it('counts by status bucket', function () {
    VpoApproval::factory()->create();
    VpoApproval::factory()->fullyApproved()->create();
    VpoApproval::factory()->rejected()->create();

    Livewire::test(Index::class)
        ->assertViewHas('kpis', fn ($k) => $k['pending'] === 1 && $k['approved'] === 1 && $k['rejected'] === 1);
});

it('deletes an approval and cascades its lines', function () {
    $a = VpoApproval::factory()->create();
    $a->items()->create(['description' => 'X', 'quantity' => 1, 'sequence_no' => 1]);

    Livewire::test(Index::class)->call('delete', $a->id);

    expect(VpoApproval::find($a->id))->toBeNull()
        ->and(VpoApprovalItem::where('vpo_approval_id', $a->id)->count())->toBe(0);
});

it('blocks the create page without permission', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('vpo_approval.view');
    $this->actingAs($user);

    $this->get(route('vpo-approval.create'))->assertForbidden();
});

it('requires authentication', function () {
    auth()->logout();
    $this->get(route('vpo-approval.index'))->assertRedirect(route('login'));
});
