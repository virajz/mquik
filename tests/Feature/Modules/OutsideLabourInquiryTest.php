<?php

use App\Models\User;
use App\Modules\ComplaintTypeMaster\Models\ComplaintTypeMaster;
use App\Modules\OutsideLabourInquiry\Livewire\Edit;
use App\Modules\OutsideLabourInquiry\Livewire\Index;
use App\Modules\OutsideLabourInquiry\Models\OutsideLabourInquiry;
use App\Modules\OutsideLabourInquiry\Models\OutsideLabourInquiryScope;
use App\Modules\OutsideLabourRejectionReasonMaster\Models\OutsideLabourRejectionReasonMaster;
use App\Modules\ServiceSpecialistMaster\Models\ServiceSpecialistMaster;
use App\Modules\VendorMaster\Models\VendorMaster;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(adminUser());
});

it('renders the index page', function () {
    OutsideLabourInquiry::factory()->count(3)->create();

    $this->get(route('outside-labour-inquiry.index'))
        ->assertOk()
        ->assertSeeLivewire(Index::class);
});

it('renders the create page', function () {
    $this->get(route('outside-labour-inquiry.create'))
        ->assertOk()
        ->assertSeeLivewire(Edit::class);
});

it('creates an inquiry, stamps the OLI number, and redirects to the index', function () {
    $type = ServiceSpecialistMaster::factory()->create(['name' => 'DENTING']);

    Livewire::test(Edit::class)
        ->set('inquiry_type_id', $type->id)
        ->set('scopes.0.description', 'full body denting')
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect(route('outside-labour-inquiry.index'));

    $inquiry = OutsideLabourInquiry::first();
    expect($inquiry->inquiry_no)->toBe('OLI-'.str_pad((string) $inquiry->id, 5, '0', STR_PAD_LEFT))
        ->and($inquiry->inquiry_type_id)->toBe($type->id)
        ->and($inquiry->status)->toBe(OutsideLabourInquiry::STATUS_RESPONSE_PENDING)
        ->and($inquiry->scopes)->toHaveCount(1)
        ->and($inquiry->scopes->first()->description)->toBe('FULL BODY DENTING');
});

it('persists scope lines with labour, job description and complaint links', function () {
    $inquiry = OutsideLabourInquiry::factory()->create();
    $complaint = ComplaintTypeMaster::factory()->create();

    Livewire::test(Edit::class, ['outsideLabourInquiry' => $inquiry])
        ->set('scopes.0.description', 'repaint bumper')
        ->set('scopes.0.complaint_type_id', $complaint->id)
        ->call('addScope')
        ->set('scopes.1.description', 'polish')
        ->call('save')
        ->assertHasNoErrors();

    $inquiry->refresh()->load('scopes');
    expect($inquiry->scopes)->toHaveCount(2)
        ->and($inquiry->scopes->first()->complaint_type_id)->toBe($complaint->id)
        ->and($inquiry->scopes->first()->description)->toBe('REPAINT BUMPER');
});

it('strips blank scope rows before validating', function () {
    $inquiry = OutsideLabourInquiry::factory()->create();

    Livewire::test(Edit::class, ['outsideLabourInquiry' => $inquiry])
        ->set('scopes.0.description', 'denting')
        ->call('addScope') // blank trailing row
        ->call('save')
        ->assertHasNoErrors();

    expect($inquiry->fresh()->scopes)->toHaveCount(1);
});

it('requires a rejection reason when the status is rejected', function () {
    $inquiry = OutsideLabourInquiry::factory()->create();

    Livewire::test(Edit::class, ['outsideLabourInquiry' => $inquiry])
        ->set('scopes.0.description', 'denting')
        ->set('status', OutsideLabourInquiry::STATUS_REJECTED)
        ->call('save')
        ->assertHasErrors(['rejection_reason_id']);

    $reason = OutsideLabourRejectionReasonMaster::factory()->create();

    Livewire::test(Edit::class, ['outsideLabourInquiry' => $inquiry])
        ->set('scopes.0.description', 'denting')
        ->set('status', OutsideLabourInquiry::STATUS_REJECTED)
        ->set('rejection_reason_id', $reason->id)
        ->call('save')
        ->assertHasNoErrors();
});

it('requires custom TAT days when TAT option is custom', function () {
    $inquiry = OutsideLabourInquiry::factory()->create();

    Livewire::test(Edit::class, ['outsideLabourInquiry' => $inquiry])
        ->set('scopes.0.description', 'denting')
        ->set('tat_option', 'custom')
        ->call('save')
        ->assertHasErrors(['tat_custom_days']);
});

it('clears custom TAT days when a preset option is chosen', function () {
    $inquiry = OutsideLabourInquiry::factory()->create();

    Livewire::test(Edit::class, ['outsideLabourInquiry' => $inquiry])
        ->set('scopes.0.description', 'denting')
        ->set('tat_option', 'two_days')
        ->set('tat_custom_days', 9)
        ->call('save')
        ->assertHasNoErrors();

    expect($inquiry->fresh()->tat_custom_days)->toBeNull();
});

it('stores an uploaded attachment', function () {
    Storage::fake('public');
    $inquiry = OutsideLabourInquiry::factory()->create();

    Livewire::test(Edit::class, ['outsideLabourInquiry' => $inquiry])
        ->set('scopes.0.description', 'denting')
        ->call('addAttachment')
        ->set('attachmentFiles.0', UploadedFile::fake()->create('quote.pdf', 100, 'application/pdf'))
        ->call('save')
        ->assertHasNoErrors();

    $inquiry->refresh()->load('attachments');
    expect($inquiry->attachments)->toHaveCount(1)
        ->and($inquiry->attachments->first()->kind)->toBe('pdf');
    Storage::disk('public')->assertExists($inquiry->attachments->first()->path);
});

it('drops attachment rows that never got a file', function () {
    $inquiry = OutsideLabourInquiry::factory()->create();

    Livewire::test(Edit::class, ['outsideLabourInquiry' => $inquiry])
        ->set('scopes.0.description', 'denting')
        ->call('addAttachment') // no file
        ->call('save')
        ->assertHasNoErrors();

    expect($inquiry->fresh()->attachments)->toHaveCount(0);
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

it('counts open, pending, completed and cancelled inquiries', function () {
    OutsideLabourInquiry::factory()->create(); // response_pending
    OutsideLabourInquiry::factory()->responded()->create();
    OutsideLabourInquiry::factory()->issued()->create();
    OutsideLabourInquiry::factory()->rejected()->create();

    Livewire::test(Index::class)
        ->assertViewHas('kpis', fn ($kpis) => $kpis['open'] === 2
            && $kpis['pending'] === 1
            && $kpis['completed'] === 1
            && $kpis['cancelled'] === 1);
});

it('deletes an inquiry and cascades its scopes', function () {
    $inquiry = OutsideLabourInquiry::factory()->create();
    $inquiry->scopes()->create(['description' => 'X', 'sequence_no' => 1]);

    Livewire::test(Index::class)->call('delete', $inquiry->id);

    expect(OutsideLabourInquiry::find($inquiry->id))->toBeNull()
        ->and(OutsideLabourInquiryScope::where('outside_labour_inquiry_id', $inquiry->id)->count())->toBe(0);
});

it('blocks the create page for a user without create permission', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('outside_labour_inquiry.view');
    $this->actingAs($user);

    $this->get(route('outside-labour-inquiry.create'))->assertForbidden();
});

it('requires authentication', function () {
    auth()->logout();
    $this->get(route('outside-labour-inquiry.index'))->assertRedirect(route('login'));
});
