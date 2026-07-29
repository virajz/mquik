<?php

use App\Models\User;
use App\Modules\JobCardCancelApproval\Livewire\Edit;
use App\Modules\JobCardCancelApproval\Livewire\Index;
use App\Modules\JobCardCancelApproval\Models\JobCardCancelApproval;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(adminUser());
});

it('renders the index page', function () {
    JobCardCancelApproval::factory()->count(3)->create();

    $this->get(route('job-card-cancel-approval.index'))
        ->assertOk()
        ->assertSeeLivewire(Index::class);
});

it('renders the create page', function () {
    $this->get(route('job-card-cancel-approval.create'))
        ->assertOk()
        ->assertSeeLivewire(Edit::class);
});

it('creates a request, stamps the number, and stores multi-select impacts', function () {
    Livewire::test(Edit::class)
        ->set('cancellation_type', 'duplicate')
        ->set('approval_level', 'l1_advisor')
        ->set('impacts', ['refund_payment', 'reverse_parts_in_stock'])
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect(route('job-card-cancel-approval.index'));

    $a = JobCardCancelApproval::first();
    expect($a->approval_no)->toBe('JCA-'.str_pad((string) $a->id, 5, '0', STR_PAD_LEFT))
        ->and($a->cancellation_type)->toBe('duplicate')
        ->and($a->impacts)->toBe(['refund_payment', 'reverse_parts_in_stock']);
});

it('requires a decided-at timestamp when approved or rejected', function () {
    Livewire::test(Edit::class)
        ->set('status', JobCardCancelApproval::STATUS_APPROVED)
        ->call('save')
        ->assertHasErrors(['decided_at']);
});

it('requires an approval rejection reason when rejected', function () {
    Livewire::test(Edit::class)
        ->set('status', JobCardCancelApproval::STATUS_REJECTED)
        ->set('decided_at', now()->format('Y-m-d\TH:i'))
        ->call('save')
        ->assertHasErrors(['approval_rejection_reason']);
});

it('validates cancellation type and impacts against the allowed lists', function () {
    Livewire::test(Edit::class)
        ->set('cancellation_type', 'nope')
        ->set('impacts', ['not_a_real_impact'])
        ->call('save')
        ->assertHasErrors(['cancellation_type', 'impacts.0']);
});

it('filters by status and type', function () {
    $approved = JobCardCancelApproval::factory()->approved()->create(['cancellation_type' => 'duplicate']);
    $pending = JobCardCancelApproval::factory()->create(['cancellation_type' => 'wrong_entry']);

    Livewire::test(Index::class)
        ->set('statusFilter', JobCardCancelApproval::STATUS_APPROVED)
        ->assertSee($approved->approval_no)
        ->assertDontSee($pending->approval_no);

    Livewire::test(Index::class)
        ->set('typeFilter', 'duplicate')
        ->assertSee($approved->approval_no)
        ->assertDontSee($pending->approval_no);
});

it('exports the job card cancel report as CSV', function () {
    JobCardCancelApproval::factory()->approved()->create();

    Livewire::test(Index::class)
        ->call('download')
        ->assertFileDownloaded();
});

it('counts by status', function () {
    JobCardCancelApproval::factory()->create();
    JobCardCancelApproval::factory()->underReview()->create();
    JobCardCancelApproval::factory()->approved()->create();
    JobCardCancelApproval::factory()->rejected()->create();

    Livewire::test(Index::class)
        ->assertViewHas('kpis', fn ($kpis) => $kpis['pending'] === 1
            && $kpis['under_review'] === 1
            && $kpis['approved'] === 1
            && $kpis['rejected'] === 1);
});

it('deletes a request from the index', function () {
    $a = JobCardCancelApproval::factory()->create();

    Livewire::test(Index::class)->call('delete', $a->id);

    expect(JobCardCancelApproval::find($a->id))->toBeNull();
});

it('blocks the create page without permission', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('job_card_cancel_approval.view');
    $this->actingAs($user);

    $this->get(route('job-card-cancel-approval.create'))->assertForbidden();
});

it('requires authentication', function () {
    auth()->logout();
    $this->get(route('job-card-cancel-approval.index'))->assertRedirect(route('login'));
});
