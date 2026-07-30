<?php

use App\Models\User;
use App\Modules\EmployeeMaster\Models\EmployeeMaster;
use App\Modules\InternalPartsInquiry\Livewire\Edit;
use App\Modules\InternalPartsInquiry\Livewire\Index;
use App\Modules\InternalPartsInquiry\Models\InternalPartsInquiry;
use App\Modules\InternalPartsInquiry\Models\InternalPartsInquiryItem;
use App\Modules\Inventory\Models\StockEntry;
use App\Modules\IpiRejectionReasonMaster\Models\IpiRejectionReasonMaster;
use App\Modules\JobCard\Models\JobCard;
use App\Modules\SpareMaster\Models\SpareMaster;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(adminUser());
});

it('renders the index page', function () {
    InternalPartsInquiry::factory()->count(3)->create();

    $this->get(route('internal-parts-inquiry.index'))
        ->assertOk()
        ->assertSeeLivewire(Index::class);
});

it('renders the create page', function () {
    $this->get(route('internal-parts-inquiry.create'))
        ->assertOk()
        ->assertSeeLivewire(Edit::class);
});

it('filters records by search on ipi_no', function () {
    $a = InternalPartsInquiry::factory()->create();
    $b = InternalPartsInquiry::factory()->create();
    $a->forceFill(['ipi_no' => 'IPI-AAAAA'])->saveQuietly();
    $b->forceFill(['ipi_no' => 'IPI-BBBBB'])->saveQuietly();

    Livewire::test(Index::class)
        ->set('search', 'IPI-AAAAA')
        ->assertSee('IPI-AAAAA')
        ->assertDontSee('IPI-BBBBB');
});

it('filters by status', function () {
    $ordered = InternalPartsInquiry::factory()->ordered()->create();
    $pending = InternalPartsInquiry::factory()->create();

    Livewire::test(Index::class)
        ->set('statusFilter', InternalPartsInquiry::STATUS_ORDERED)
        ->assertSee($ordered->ipi_no)
        ->assertDontSee($pending->ipi_no);
});

it('filters by inquiry type', function () {
    $jc = InternalPartsInquiry::factory()->create(['inquiry_type' => 'against_job_card']);
    $stock = InternalPartsInquiry::factory()->create(['inquiry_type' => 'stock_replenishment']);

    Livewire::test(Index::class)
        ->set('typeFilter', 'stock_replenishment')
        ->assertSee($stock->ipi_no)
        ->assertDontSee($jc->ipi_no);
});

it('creates an inquiry with a part line and redirects to the index', function () {
    $emp = EmployeeMaster::factory()->create();

    Livewire::test(Edit::class)
        ->set('requested_by_employee_id', $emp->id)
        ->set('inquiry_type', 'stock_replenishment')
        ->set('items.0.description', 'brake pad set')
        ->set('items.0.quantity', 2)
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect(route('internal-parts-inquiry.index'));

    $inquiry = InternalPartsInquiry::first();
    expect($inquiry->ipi_no)->toBe('IPI-'.str_pad((string) $inquiry->id, 5, '0', STR_PAD_LEFT))
        ->and($inquiry->inquiry_type)->toBe('stock_replenishment')
        ->and($inquiry->status)->toBe(InternalPartsInquiry::STATUS_PENDING)
        ->and($inquiry->items)->toHaveCount(1)
        ->and($inquiry->items->first()->description)->toBe('BRAKE PAD SET')
        ->and((float) $inquiry->items->first()->quantity)->toBe(2.0);
});

it('auto-fills a line from the picked spare master', function () {
    $spare = SpareMaster::factory()->create();

    $component = Livewire::test(Edit::class)
        ->set('items.0.spare_id', $spare->id);

    expect($component->get('items.0.uom_id'))->toBe($spare->uom_id)
        ->and($component->get('items.0.part_type_id'))->toBe($spare->part_type_id)
        ->and($component->get('items.0.spare_brand_id'))->toBe($spare->spare_brand_id);
});

it('sets stock availability from the ledger when a spare is picked', function () {
    $inStock = SpareMaster::factory()->create();
    StockEntry::factory()->create(['spare_id' => $inStock->id, 'qty' => 5]);
    $outOfStock = SpareMaster::factory()->create();

    $component = Livewire::test(Edit::class)->set('items.0.spare_id', $inStock->id);
    expect($component->get('items.0.stock_status'))->toBe('available')
        ->and($component->instance()->onHandQty(0))->toBe(5.0);

    $component->set('items.0.spare_id', $outOfStock->id);
    expect($component->get('items.0.stock_status'))->toBe('not_available');
});

it('prefills from a job card when raised via ?from-job-card', function () {
    $jobCard = JobCard::factory()->create();

    $component = Livewire::test(Edit::class, ['fromJobCard' => $jobCard->id]);

    expect($component->get('job_card_id'))->toBe($jobCard->id)
        ->and($component->get('customer_id'))->toBe($jobCard->customer_id)
        ->and($component->get('customer_vehicle_id'))->toBe($jobCard->customer_vehicle_id)
        ->and($component->get('inquiry_type'))->toBe('against_job_card');
});

it('stamps responded_at and responder when the store marks a responded status', function () {
    $inquiry = InternalPartsInquiry::factory()->create();
    $store = EmployeeMaster::factory()->create();
    expect($inquiry->responded_at)->toBeNull();

    Livewire::test(Edit::class, ['internalPartsInquiry' => $inquiry])
        ->set('items.0.description', 'brake pad')
        ->set('status', InternalPartsInquiry::STATUS_FULLY_AVAILABLE)
        ->set('responded_by_employee_id', $store->id)
        ->call('save')
        ->assertHasNoErrors();

    $fresh = $inquiry->fresh();
    expect($fresh->responded_at)->not->toBeNull()
        ->and($fresh->responded_by_employee_id)->toBe($store->id);
});

it('requires a description on every part line', function () {
    $emp = EmployeeMaster::factory()->create();

    Livewire::test(Edit::class)
        ->set('requested_by_employee_id', $emp->id)
        ->set('items.0.description', '')
        ->call('save')
        ->assertHasErrors(['items.0.description']);
});

it('requires a rejection reason when status is not available', function () {
    $inquiry = InternalPartsInquiry::factory()->create();

    Livewire::test(Edit::class, ['internalPartsInquiry' => $inquiry])
        ->set('items.0.description', 'x')
        ->set('status', InternalPartsInquiry::STATUS_NOT_AVAILABLE)
        ->call('save')
        ->assertHasErrors(['rejection_reason_id']);

    $reason = IpiRejectionReasonMaster::factory()->create();

    Livewire::test(Edit::class, ['internalPartsInquiry' => $inquiry])
        ->set('items.0.description', 'x')
        ->set('status', InternalPartsInquiry::STATUS_NOT_AVAILABLE)
        ->set('rejection_reason_id', $reason->id)
        ->call('save')
        ->assertHasNoErrors();
});

it('requires custom TAT days when TAT is custom', function () {
    $inquiry = InternalPartsInquiry::factory()->create();

    Livewire::test(Edit::class, ['internalPartsInquiry' => $inquiry])
        ->set('items.0.description', 'x')
        ->set('tat_option', 'custom')
        ->call('save')
        ->assertHasErrors(['tat_custom_days']);
});

it('stores an uploaded attachment', function () {
    Storage::fake('public');
    $inquiry = InternalPartsInquiry::factory()->create();

    Livewire::test(Edit::class, ['internalPartsInquiry' => $inquiry])
        ->set('items.0.description', 'x')
        ->call('addAttachment')
        ->set('attachmentFiles.0', UploadedFile::fake()->image('part.jpg'))
        ->call('save')
        ->assertHasNoErrors();

    $inquiry->refresh()->load('attachments');
    expect($inquiry->attachments)->toHaveCount(1)
        ->and($inquiry->attachments->first()->kind)->toBe('image');
    Storage::disk('public')->assertExists($inquiry->attachments->first()->path);
});

it('counts open, pending, ordered and cancelled inquiries', function () {
    InternalPartsInquiry::factory()->create(); // pending
    InternalPartsInquiry::factory()->inProgress()->create();
    InternalPartsInquiry::factory()->ordered()->create();
    InternalPartsInquiry::factory()->cancelled()->create();

    Livewire::test(Index::class)
        ->assertViewHas('kpis', fn ($kpis) => $kpis['open'] === 2
            && $kpis['pending'] === 1
            && $kpis['ordered'] === 1
            && $kpis['cancelled'] === 1);
});

it('deletes a record and cascades its parts', function () {
    $inquiry = InternalPartsInquiry::factory()->create();
    $inquiry->items()->create(['description' => 'X', 'quantity' => 1, 'sequence_no' => 1]);

    Livewire::test(Index::class)->call('delete', $inquiry->id);

    expect(InternalPartsInquiry::find($inquiry->id))->toBeNull()
        ->and(InternalPartsInquiryItem::where('internal_parts_inquiry_id', $inquiry->id)->count())->toBe(0);
});

it('blocks the create page for a user without create permission', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('internal_parts_inquiry.view');
    $this->actingAs($user);

    $this->get(route('internal-parts-inquiry.create'))->assertForbidden();
});

it('requires authentication', function () {
    auth()->logout();
    $this->get(route('internal-parts-inquiry.index'))->assertRedirect(route('login'));
});

it('denies index access without permission', function () {
    $this->actingAs(User::factory()->create());
    $this->get(route('internal-parts-inquiry.index'))->assertForbidden();
});
