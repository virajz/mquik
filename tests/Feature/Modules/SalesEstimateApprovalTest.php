<?php

use App\Models\User;
use App\Modules\SalesEstimateApproval\Livewire\Edit;
use App\Modules\SalesEstimateApproval\Livewire\Index;
use App\Modules\SalesEstimateApproval\Models\SalesEstimateApproval;
use App\Modules\SalesEstimateApproval\Models\SalesEstimateApprovalItem;
use App\Modules\SpareMaster\Models\SpareMaster;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(adminUser());
});

it('renders the index page', function () {
    SalesEstimateApproval::factory()->count(3)->create();

    $this->get(route('sales-estimate-approval.index'))
        ->assertOk()
        ->assertSeeLivewire(Index::class);
});

it('renders the create page', function () {
    $this->get(route('sales-estimate-approval.create'))
        ->assertOk()
        ->assertSeeLivewire(Edit::class);
});

it('creates an approval with a depreciated line and redirects', function () {
    $spare = SpareMaster::factory()->create();

    Livewire::test(Edit::class)
        ->set('approval_type', 'regular')
        ->set('approval_authorisation', 'both')
        ->call('addItem', 'spare')
        ->set('items.0.spare_id', $spare->id)
        ->set('items.0.description', 'bumper')
        ->set('items.0.quantity', 1)
        ->set('items.0.unit_rate', 1000)
        ->set('items.0.line_approval', 'replace')
        ->set('items.0.depreciation_category', 'plastic')
        ->set('items.0.depreciation_percent', 30)
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect(route('sales-estimate-approval.index'));

    $a = SalesEstimateApproval::with('items')->first();
    expect($a->approval_no)->toBe('SEA-'.str_pad((string) $a->id, 5, '0', STR_PAD_LEFT))
        ->and($a->approval_authorisation)->toBe('both');

    $item = $a->items->first();
    expect($item->line_approval)->toBe('replace')
        ->and($item->depreciation_category)->toBe('plastic')
        ->and($item->netAfterDepreciation())->toBe(700.0);
});

it('stamps approved_at when status becomes fully approved', function () {
    $a = SalesEstimateApproval::factory()->create();

    Livewire::test(Edit::class, ['salesEstimateApproval' => $a])
        ->set('status', SalesEstimateApproval::STATUS_FULLY_APPROVED)
        ->call('save')
        ->assertHasNoErrors();

    expect($a->fresh()->approved_at)->not->toBeNull();
});

it('requires a rejection reason when rejected', function () {
    Livewire::test(Edit::class)
        ->set('status', SalesEstimateApproval::STATUS_REJECTED)
        ->call('save')
        ->assertHasErrors(['rejection_reason']);
});

it('counts pending customer and insurance approvals', function () {
    // Awaiting customer.
    SalesEstimateApproval::factory()->create(['approval_authorisation' => 'customer', 'customer_approved_at' => null]);
    // Awaiting insurance.
    SalesEstimateApproval::factory()->create(['approval_authorisation' => 'insurance', 'insurance_approved_at' => null]);
    // Awaiting both -> counts in both.
    SalesEstimateApproval::factory()->create(['approval_authorisation' => 'both']);
    // Fully approved today.
    SalesEstimateApproval::factory()->fullyApproved()->create();

    Livewire::test(Index::class)
        ->assertViewHas('kpis', fn ($kpis) => $kpis['pending_customer'] === 2 // customer + both
            && $kpis['pending_insurance'] === 2 // insurance + both
            && $kpis['today_approved'] === 1);
});

it('filters by status and authorisation', function () {
    $rej = SalesEstimateApproval::factory()->rejected()->create(['approval_authorisation' => 'insurance']);
    $cust = SalesEstimateApproval::factory()->create(['approval_authorisation' => 'customer']);

    Livewire::test(Index::class)
        ->set('statusFilter', SalesEstimateApproval::STATUS_REJECTED)
        ->assertSee($rej->approval_no)
        ->assertDontSee($cust->approval_no);

    Livewire::test(Index::class)
        ->set('authFilter', 'customer')
        ->assertSee($cust->approval_no)
        ->assertDontSee($rej->approval_no);
});

it('exports the estimate analysis report as CSV', function () {
    SalesEstimateApproval::factory()->fullyApproved()->create();

    Livewire::test(Index::class)
        ->call('download')
        ->assertFileDownloaded();
});

it('deletes an approval and cascades its lines', function () {
    $a = SalesEstimateApproval::factory()->create();
    $a->items()->create(['line_type' => 'labour', 'description' => 'X', 'quantity' => 1, 'sequence_no' => 1]);

    Livewire::test(Index::class)->call('delete', $a->id);

    expect(SalesEstimateApproval::find($a->id))->toBeNull()
        ->and(SalesEstimateApprovalItem::where('sales_estimate_approval_id', $a->id)->count())->toBe(0);
});

it('blocks the create page without permission', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('sales_estimate_approval.view');
    $this->actingAs($user);

    $this->get(route('sales-estimate-approval.create'))->assertForbidden();
});

it('requires authentication', function () {
    auth()->logout();
    $this->get(route('sales-estimate-approval.index'))->assertRedirect(route('login'));
});
