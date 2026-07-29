<?php

use App\Models\User;
use App\Modules\IpoCancelApproval\Livewire\Edit;
use App\Modules\IpoCancelApproval\Livewire\Index;
use App\Modules\IpoCancelApproval\Models\IpoCancelApproval;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(adminUser());
});

it('renders the index page', function () {
    IpoCancelApproval::factory()->count(3)->create();

    $this->get(route('ipo-cancel-approval.index'))
        ->assertOk()
        ->assertSeeLivewire(Index::class);
});

it('renders the create page', function () {
    $this->get(route('ipo-cancel-approval.create'))
        ->assertOk()
        ->assertSeeLivewire(Edit::class);
});

it('raises a request, stamps the number, and stores multi-select impacts', function () {
    Livewire::test(Edit::class)
        ->set('cancellation_reason', 'excess_qty')
        ->set('cancellation_category', 'operational_error')
        ->set('impacts', ['return_to_vendor', 'refund_payment'])
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect(route('ipo-cancel-approval.index'));

    $a = IpoCancelApproval::first();
    expect($a->cancel_no)->toBe('ICR-'.str_pad((string) $a->id, 5, '0', STR_PAD_LEFT))
        ->and($a->cancellation_reason)->toBe('excess_qty')
        ->and($a->impacts)->toBe(['return_to_vendor', 'refund_payment']);
});

it('requires a decided-at timestamp when approved or rejected', function () {
    Livewire::test(Edit::class)
        ->set('status', IpoCancelApproval::STATUS_APPROVED)
        ->call('save')
        ->assertHasErrors(['decided_at']);
});

it('requires a rejection reason when rejected', function () {
    Livewire::test(Edit::class)
        ->set('status', IpoCancelApproval::STATUS_REJECTED)
        ->set('decided_at', now()->format('Y-m-d\TH:i'))
        ->call('save')
        ->assertHasErrors(['rejection_reason']);
});

it('validates impacts against the allowed list', function () {
    Livewire::test(Edit::class)
        ->set('impacts', ['not_a_real_impact'])
        ->call('save')
        ->assertHasErrors(['impacts.0']);
});

it('stores an uploaded document attachment', function () {
    Storage::fake('public');
    $a = IpoCancelApproval::factory()->create();

    Livewire::test(Edit::class, ['ipoCancelApproval' => $a])
        ->call('addAttachment')
        ->set('attachmentFiles.0', UploadedFile::fake()->create('proof.pdf', 40, 'application/pdf'))
        ->call('save')
        ->assertHasNoErrors();

    $a->refresh()->load('attachments');
    expect($a->attachments)->toHaveCount(1)
        ->and($a->attachments->first()->kind)->toBe('pdf');
    Storage::disk('public')->assertExists($a->attachments->first()->path);
});

it('filters by status', function () {
    $approved = IpoCancelApproval::factory()->approved()->create();
    $review = IpoCancelApproval::factory()->create();

    Livewire::test(Index::class)
        ->set('statusFilter', IpoCancelApproval::STATUS_APPROVED)
        ->assertSee($approved->cancel_no)
        ->assertDontSee($review->cancel_no);
});

it('exports the IPO report as CSV', function () {
    IpoCancelApproval::factory()->approved()->create();

    Livewire::test(Index::class)->call('download')->assertFileDownloaded();
});

it('counts by status bucket', function () {
    IpoCancelApproval::factory()->create();
    IpoCancelApproval::factory()->approved()->create();
    IpoCancelApproval::factory()->rejected()->create();

    Livewire::test(Index::class)
        ->assertViewHas('kpis', fn ($kpis) => $kpis['under_review'] === 1
            && $kpis['approved'] === 1
            && $kpis['rejected'] === 1);
});

it('deletes a request from the index', function () {
    $a = IpoCancelApproval::factory()->create();

    Livewire::test(Index::class)->call('delete', $a->id);

    expect(IpoCancelApproval::find($a->id))->toBeNull();
});

it('blocks the create page without permission', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('ipo_cancel_approval.view');
    $this->actingAs($user);

    $this->get(route('ipo-cancel-approval.create'))->assertForbidden();
});

it('requires authentication', function () {
    auth()->logout();
    $this->get(route('ipo-cancel-approval.index'))->assertRedirect(route('login'));
});
