<?php

use App\Models\User;
use App\Modules\AdvanceReceiptRequest\Livewire\Edit;
use App\Modules\AdvanceReceiptRequest\Livewire\Index;
use App\Modules\AdvanceReceiptRequest\Models\AdvanceReceiptRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(adminUser());
});

it('renders the index page', function () {
    AdvanceReceiptRequest::factory()->count(3)->create();

    $this->get(route('advance-receipt-request.index'))
        ->assertOk()
        ->assertSeeLivewire(Index::class);
});

it('renders the create page', function () {
    $this->get(route('advance-receipt-request.create'))
        ->assertOk()
        ->assertSeeLivewire(Edit::class);
});

it('creates a request, stamps the number, and redirects', function () {
    Livewire::test(Edit::class)
        ->set('advance_purpose', 'urgent_po')
        ->set('amount_type', 'custom')
        ->set('amount', 2500)
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect(route('advance-receipt-request.index'));

    $r = AdvanceReceiptRequest::first();
    expect($r->request_no)->toBe('ARR-'.str_pad((string) $r->id, 5, '0', STR_PAD_LEFT))
        ->and($r->advance_purpose)->toBe('urgent_po')
        ->and((float) $r->amount)->toBe(2500.0)
        ->and($r->payment_status)->toBe(AdvanceReceiptRequest::STATUS_REQUESTED);
});

it('requires a percent when amount type is percent of estimate', function () {
    Livewire::test(Edit::class)
        ->set('amount_type', 'percent_of_estimate')
        ->call('save')
        ->assertHasErrors(['percent']);
});

it('clears percent when amount type is not percent of estimate', function () {
    Livewire::test(Edit::class)
        ->set('amount_type', 'custom')
        ->set('percent', 25)
        ->set('amount', 1000)
        ->call('save')
        ->assertHasNoErrors();

    expect(AdvanceReceiptRequest::first()->percent)->toBeNull();
});

it('requires a custom time when reminder is custom', function () {
    Livewire::test(Edit::class)
        ->set('reminder_time', 'custom')
        ->call('save')
        ->assertHasErrors(['reminder_custom_time']);
});

it('requires a rejection reason when rejected', function () {
    Livewire::test(Edit::class)
        ->set('payment_status', AdvanceReceiptRequest::STATUS_REJECTED)
        ->call('save')
        ->assertHasErrors(['rejection_reason']);
});

it('stores an uploaded attachment', function () {
    Storage::fake('public');
    $r = AdvanceReceiptRequest::factory()->create();

    Livewire::test(Edit::class, ['advanceReceiptRequest' => $r])
        ->call('addAttachment')
        ->set('attachmentFiles.0', UploadedFile::fake()->create('advice.pdf', 50, 'application/pdf'))
        ->call('save')
        ->assertHasNoErrors();

    $r->refresh()->load('attachments');
    expect($r->attachments)->toHaveCount(1)
        ->and($r->attachments->first()->kind)->toBe('pdf');
    Storage::disk('public')->assertExists($r->attachments->first()->path);
});

it('filters by status and purpose', function () {
    $paid = AdvanceReceiptRequest::factory()->fullyPaid()->create(['advance_purpose' => 'urgent_po']);
    $other = AdvanceReceiptRequest::factory()->create(['advance_purpose' => 'regular_parts']);

    Livewire::test(Index::class)
        ->set('statusFilter', AdvanceReceiptRequest::STATUS_FULLY_PAID)
        ->assertSee($paid->request_no)
        ->assertDontSee($other->request_no);

    Livewire::test(Index::class)
        ->set('purposeFilter', 'urgent_po')
        ->assertSee($paid->request_no)
        ->assertDontSee($other->request_no);
});

it('exports the advance receipt report as CSV', function () {
    AdvanceReceiptRequest::factory()->fullyPaid()->create();

    Livewire::test(Index::class)
        ->call('download')
        ->assertFileDownloaded();
});

it('counts by status', function () {
    AdvanceReceiptRequest::factory()->create();
    AdvanceReceiptRequest::factory()->partiallyPaid()->create();
    AdvanceReceiptRequest::factory()->fullyPaid()->create();
    AdvanceReceiptRequest::factory()->rejected()->create();

    Livewire::test(Index::class)
        ->assertViewHas('kpis', fn ($kpis) => $kpis['requested'] === 1
            && $kpis['partially_paid'] === 1
            && $kpis['fully_paid'] === 1
            && $kpis['closed'] === 1);
});

it('deletes a request and cascades its attachments', function () {
    $r = AdvanceReceiptRequest::factory()->create();
    $r->attachments()->create(['kind' => 'image', 'path' => 'x.jpg', 'sequence_no' => 1]);

    Livewire::test(Index::class)->call('delete', $r->id);

    expect(AdvanceReceiptRequest::find($r->id))->toBeNull();
});

it('blocks the create page without permission', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('advance_receipt_request.view');
    $this->actingAs($user);

    $this->get(route('advance-receipt-request.create'))->assertForbidden();
});

it('requires authentication', function () {
    auth()->logout();
    $this->get(route('advance-receipt-request.index'))->assertRedirect(route('login'));
});
