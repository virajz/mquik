<?php

use App\Models\User;
use App\Modules\ChargeTypeMaster\Models\ChargeTypeMaster;
use App\Modules\InternalPartsInquiry\Models\InternalPartsInquiry;
use App\Modules\SpareMaster\Models\SpareMaster;
use App\Modules\VendorMaster\Models\VendorMaster;
use App\Modules\VendorPurchaseInquiry\Livewire\Edit;
use App\Modules\VendorPurchaseInquiry\Livewire\Index;
use App\Modules\VendorPurchaseInquiry\Models\VendorPurchaseInquiry;
use App\Modules\VendorPurchaseInquiry\Models\VendorPurchaseInquiryItem;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(adminUser());
});

it('renders the index page', function () {
    VendorPurchaseInquiry::factory()->count(3)->create();

    $this->get(route('vendor-purchase-inquiry.index'))
        ->assertOk()
        ->assertSeeLivewire(Index::class);
});

it('renders the create page', function () {
    $this->get(route('vendor-purchase-inquiry.create'))
        ->assertOk()
        ->assertSeeLivewire(Edit::class);
});

it('creates an RFQ, stamps the VPI number, and redirects', function () {
    $vendor = VendorMaster::factory()->create();

    Livewire::test(Edit::class)
        ->set('vendor_id', $vendor->id)
        ->set('inquiry_type', 'stock_replenishment')
        ->set('items.0.description', 'brake pad set')
        ->set('items.0.quantity', 4)
        ->set('items.0.quoted_rate', 250)
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect(route('vendor-purchase-inquiry.index'));

    $rfq = VendorPurchaseInquiry::first();
    expect($rfq->vpi_no)->toBe('VPI-'.str_pad((string) $rfq->id, 5, '0', STR_PAD_LEFT))
        ->and($rfq->vendor_id)->toBe($vendor->id)
        ->and($rfq->status)->toBe(VendorPurchaseInquiry::STATUS_PENDING)
        ->and($rfq->items)->toHaveCount(1)
        ->and($rfq->items->first()->description)->toBe('BRAKE PAD SET')
        ->and((float) $rfq->items->first()->quoted_rate)->toBe(250.0);
});

it('requires a vendor', function () {
    Livewire::test(Edit::class)
        ->set('vendor_id', null)
        ->set('items.0.description', 'x')
        ->call('save')
        ->assertHasErrors(['vendor_id']);
});

it('auto-fills a line from the picked spare master', function () {
    $spare = SpareMaster::factory()->create();

    $component = Livewire::test(Edit::class)
        ->set('items.0.spare_id', $spare->id);

    expect($component->get('items.0.uom_id'))->toBe($spare->uom_id)
        ->and($component->get('items.0.part_type_id'))->toBe($spare->part_type_id)
        ->and($component->get('items.0.spare_brand_id'))->toBe($spare->spare_brand_id);
});

it('persists warranty period with value and unit', function () {
    $inquiry = VendorPurchaseInquiry::factory()->create();

    Livewire::test(Edit::class, ['vendorPurchaseInquiry' => $inquiry])
        ->set('items.0.description', 'clutch plate')
        ->set('items.0.warranty_type', 'manufacturer')
        ->set('items.0.warranty_period_value', 12)
        ->set('items.0.warranty_period_unit', 'month')
        ->call('save')
        ->assertHasNoErrors();

    $item = $inquiry->fresh()->items->first();
    expect($item->warranty_type)->toBe('manufacturer')
        ->and($item->warranty_period_value)->toBe(12)
        ->and($item->warranty_period_unit)->toBe('month');
});

it('persists additional charge lines and drops empty ones', function () {
    $inquiry = VendorPurchaseInquiry::factory()->create();
    $freight = ChargeTypeMaster::firstOrCreate(['name' => 'FREIGHT'], ['code' => 'FRT', 'is_active' => true]);

    Livewire::test(Edit::class, ['vendorPurchaseInquiry' => $inquiry])
        ->set('items.0.description', 'x')
        ->call('addCharge')
        ->set('charges.0.charge_type_id', $freight->id)
        ->set('charges.0.amount', 500)
        ->call('addCharge') // empty, should be dropped
        ->call('save')
        ->assertHasNoErrors();

    $inquiry->refresh()->load('charges');
    expect($inquiry->charges)->toHaveCount(1)
        ->and((float) $inquiry->charges->first()->amount)->toBe(500.0);
});

it('requires custom TAT days when TAT is custom', function () {
    $inquiry = VendorPurchaseInquiry::factory()->create();

    Livewire::test(Edit::class, ['vendorPurchaseInquiry' => $inquiry])
        ->set('items.0.description', 'x')
        ->set('tat_option', 'custom')
        ->call('save')
        ->assertHasErrors(['tat_custom_days']);
});

it('stores an uploaded RFQ attachment', function () {
    Storage::fake('public');
    $inquiry = VendorPurchaseInquiry::factory()->create();

    Livewire::test(Edit::class, ['vendorPurchaseInquiry' => $inquiry])
        ->set('items.0.description', 'x')
        ->call('addAttachment')
        ->set('attachments.0.attachment_type', 'vendor_quotation')
        ->set('attachmentFiles.0', UploadedFile::fake()->create('quote.pdf', 100, 'application/pdf'))
        ->call('save')
        ->assertHasNoErrors();

    $inquiry->refresh()->load('attachments');
    expect($inquiry->attachments)->toHaveCount(1)
        ->and($inquiry->attachments->first()->kind)->toBe('pdf')
        ->and($inquiry->attachments->first()->attachment_type)->toBe('vendor_quotation');
    Storage::disk('public')->assertExists($inquiry->attachments->first()->path);
});

it('filters by status and vendor', function () {
    $vendor = VendorMaster::factory()->create();
    $mine = VendorPurchaseInquiry::factory()->completed()->create(['vendor_id' => $vendor->id]);
    $other = VendorPurchaseInquiry::factory()->create();

    Livewire::test(Index::class)
        ->set('statusFilter', VendorPurchaseInquiry::STATUS_COMPLETED)
        ->assertSee($mine->vpi_no)
        ->assertDontSee($other->vpi_no);

    Livewire::test(Index::class)
        ->set('vendorFilter', (string) $vendor->id)
        ->assertSee($mine->vpi_no)
        ->assertDontSee($other->vpi_no);
});

it('counts pending, in-progress, completed and cancelled', function () {
    VendorPurchaseInquiry::factory()->create();
    VendorPurchaseInquiry::factory()->inProgress()->create();
    VendorPurchaseInquiry::factory()->completed()->create();
    VendorPurchaseInquiry::factory()->cancelled()->create();

    Livewire::test(Index::class)
        ->assertViewHas('kpis', fn ($kpis) => $kpis['pending'] === 1
            && $kpis['in_progress'] === 1
            && $kpis['completed'] === 1
            && $kpis['cancelled'] === 1);
});

it('deletes an RFQ and cascades its lines', function () {
    $inquiry = VendorPurchaseInquiry::factory()->create();
    $inquiry->items()->create(['description' => 'X', 'quantity' => 1, 'sequence_no' => 1]);

    Livewire::test(Index::class)->call('delete', $inquiry->id);

    expect(VendorPurchaseInquiry::find($inquiry->id))->toBeNull()
        ->and(VendorPurchaseInquiryItem::where('vendor_purchase_inquiry_id', $inquiry->id)->count())->toBe(0);
});

it('blocks the create page for a user without create permission', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('vendor_purchase_inquiry.view');
    $this->actingAs($user);

    $this->get(route('vendor-purchase-inquiry.create'))->assertForbidden();
});

it('requires authentication', function () {
    auth()->logout();
    $this->get(route('vendor-purchase-inquiry.index'))->assertRedirect(route('login'));
});

it('carries forward from an IPI: links it and seeds the not-available lines', function () {
    $ipi = InternalPartsInquiry::factory()->create();
    $spare = SpareMaster::factory()->create();
    $ipi->items()->create(['description' => 'BRAKE PAD', 'quantity' => 2, 'stock_status' => 'not_available', 'spare_id' => $spare->id]);
    $ipi->items()->create(['description' => 'ENGINE OIL', 'quantity' => 1, 'stock_status' => 'available']);

    $component = Livewire::test(Edit::class, ['fromIpi' => $ipi->id]);

    expect($component->get('internal_parts_inquiry_id'))->toBe($ipi->id)
        ->and($component->get('job_card_id'))->toBe($ipi->job_card_id);

    $items = $component->get('items');
    expect($items)->toHaveCount(1)                       // only the not-available line
        ->and($items[0]['description'])->toBe('BRAKE PAD')
        ->and($items[0]['spare_id'])->toBe($spare->id)
        ->and($items[0]['stock_status'])->toBe('not_available');
});

it('persists the IPI link when a carried-forward VPI is saved', function () {
    $ipi = InternalPartsInquiry::factory()->create();
    $ipi->items()->create(['description' => 'BRAKE PAD', 'quantity' => 1, 'stock_status' => 'not_available']);

    Livewire::test(Edit::class, ['fromIpi' => $ipi->id])
        ->set('inquiry_type', 'against_job_card')
        ->set('vendor_id', VendorMaster::factory()->create()->id)
        ->call('save')
        ->assertHasNoErrors();

    expect(VendorPurchaseInquiry::latest('id')->first()->internal_parts_inquiry_id)->toBe($ipi->id);
});
