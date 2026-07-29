<?php

use App\Modules\VendorAdvanceRequest\Livewire\Edit;
use App\Modules\VendorAdvanceRequest\Livewire\Index;
use App\Modules\VendorAdvanceRequest\Models\VendorAdvanceRequest;
use App\Modules\VendorMaster\Models\VendorMaster;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(adminUser());
});

it('renders the index page', function () {
    VendorAdvanceRequest::factory()->count(3)->create();

    $this->get(route('vendor-advance-request.index'))->assertOk()->assertSeeLivewire(Index::class);
});

it('renders the create page with a pre-seeded document checklist', function () {
    Livewire::test(Edit::class)
        ->assertOk()
        ->assertCount('documents', count(VendorAdvanceRequest::standardDocuments()));
});

it('creates a request, stamps the VAR number and redirects', function () {
    $vendor = VendorMaster::factory()->create();

    Livewire::test(Edit::class)
        ->set('vendor_id', $vendor->id)
        ->set('advance_reason', 'vendor_requires_advance')
        ->set('payment_mode', 'neft')
        ->set('amount', 12500)
        ->set('documents.0.is_provided', true)
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect(route('vendor-advance-request.index'));

    $r = VendorAdvanceRequest::with('documents')->first();
    expect($r->request_no)->toBe('VAR-'.str_pad((string) $r->id, 5, '0', STR_PAD_LEFT))
        ->and($r->vendor_id)->toBe($vendor->id)
        ->and((float) $r->amount)->toBe(12500.0)
        ->and($r->documents)->toHaveCount(count(VendorAdvanceRequest::standardDocuments()))
        ->and($r->documents->first()->is_provided)->toBeTrue();
});

it('requires a vendor', function () {
    Livewire::test(Edit::class)
        ->set('vendor_id', null)
        ->call('save')
        ->assertHasErrors(['vendor_id']);
});

it('requires a hold reason when status is on hold', function () {
    $vendor = VendorMaster::factory()->create();

    Livewire::test(Edit::class)
        ->set('vendor_id', $vendor->id)
        ->set('status', VendorAdvanceRequest::STATUS_ON_HOLD)
        ->call('save')
        ->assertHasErrors(['hold_reason']);
});

it('requires a rejection reason when status is rejected', function () {
    $vendor = VendorMaster::factory()->create();

    Livewire::test(Edit::class)
        ->set('vendor_id', $vendor->id)
        ->set('status', VendorAdvanceRequest::STATUS_REJECTED)
        ->call('save')
        ->assertHasErrors(['rejection_reason']);
});

it('requires custom reminder days when frequency is custom', function () {
    $vendor = VendorMaster::factory()->create();

    Livewire::test(Edit::class)
        ->set('vendor_id', $vendor->id)
        ->set('reminder_frequency', 'custom')
        ->call('save')
        ->assertHasErrors(['reminder_custom_days']);
});

it('uppercases notes and syncs the document checklist on save', function () {
    $vendor = VendorMaster::factory()->create();

    Livewire::test(Edit::class)
        ->set('vendor_id', $vendor->id)
        ->set('notes', 'urgent import')
        ->set('documents.0.document_name', 'gst certificate')
        ->call('save')
        ->assertHasNoErrors();

    $r = VendorAdvanceRequest::with('documents')->first();
    expect($r->notes)->toBe('URGENT IMPORT')
        ->and($r->documents->first()->document_name)->toBe('GST CERTIFICATE');
});

it('edits an existing request and keeps its number', function () {
    $request = VendorAdvanceRequest::factory()->create(['amount' => 1000]);

    Livewire::test(Edit::class, ['vendorAdvanceRequest' => $request])
        ->set('amount', 4000)
        ->set('status', VendorAdvanceRequest::STATUS_FULLY_PAID)
        ->call('save')
        ->assertHasNoErrors();

    $request->refresh();
    expect((float) $request->amount)->toBe(4000.0)
        ->and($request->status)->toBe(VendorAdvanceRequest::STATUS_FULLY_PAID);
});

it('reports KPI counts on the index', function () {
    VendorAdvanceRequest::factory()->count(2)->create();
    VendorAdvanceRequest::factory()->paid()->create();
    VendorAdvanceRequest::factory()->rejected()->create();

    Livewire::test(Index::class)
        ->assertViewHas('kpis', fn ($kpis) => $kpis['pending'] === 2 && $kpis['paid'] === 1 && $kpis['rejected'] === 1);
});

it('deletes a request', function () {
    $request = VendorAdvanceRequest::factory()->create();

    Livewire::test(Index::class)
        ->call('delete', $request->id);

    expect(VendorAdvanceRequest::find($request->id))->toBeNull();
});

it('downloads the payment report as a CSV stream', function () {
    VendorAdvanceRequest::factory()->count(2)->create();

    Livewire::test(Index::class)->call('download')->assertFileDownloaded();
});
