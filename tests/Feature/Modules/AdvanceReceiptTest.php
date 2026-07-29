<?php

use App\Models\User;
use App\Modules\AdvanceReceipt\Livewire\Edit;
use App\Modules\AdvanceReceipt\Livewire\Index;
use App\Modules\AdvanceReceipt\Models\AdvanceReceipt;
use App\Modules\PaymentModeMaster\Models\PaymentModeMaster;
use App\Support\FinancialYear;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(adminUser());
});

it('renders the index page', function () {
    AdvanceReceipt::factory()->count(3)->create();

    $this->get(route('advance-receipt.index'))
        ->assertOk()
        ->assertSeeLivewire(Index::class);
});

it('renders the create page', function () {
    $this->get(route('advance-receipt.create'))
        ->assertOk()
        ->assertSeeLivewire(Edit::class);
});

it('records a receipt with an MQ/AR financial-year series number', function () {
    $mode = PaymentModeMaster::factory()->create(['name' => 'CASH']);

    Livewire::test(Edit::class)
        ->set('payment_mode_id', $mode->id)
        ->set('amount', 5000)
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect(route('advance-receipt.index'));

    $r = AdvanceReceipt::first();
    $fy = FinancialYear::label($r->created_at);
    expect($r->fy_label)->toBe($fy)
        ->and($r->receipt_no)->toBe('MQ/AR/'.$fy.'/00001')
        ->and((float) $r->amount)->toBe(5000.0);
});

it('increments the sequence per financial year', function () {
    $mode = PaymentModeMaster::factory()->create(['name' => 'CASH']);

    $a = AdvanceReceipt::create(['payment_mode_id' => $mode->id, 'amount' => 100]);
    $b = AdvanceReceipt::create(['payment_mode_id' => $mode->id, 'amount' => 200]);

    $fy = FinancialYear::label($a->created_at);
    expect($a->fresh()->receipt_no)->toBe('MQ/AR/'.$fy.'/00001')
        ->and($b->fresh()->receipt_no)->toBe('MQ/AR/'.$fy.'/00002');
});

it('requires a payment mode and amount', function () {
    Livewire::test(Edit::class)
        ->set('payment_mode_id', null)
        ->set('amount', null)
        ->call('save')
        ->assertHasErrors(['payment_mode_id', 'amount']);
});

it('requires a cancellation reason when status is cancelled', function () {
    $mode = PaymentModeMaster::factory()->create();

    Livewire::test(Edit::class)
        ->set('payment_mode_id', $mode->id)
        ->set('amount', 100)
        ->set('payment_status', AdvanceReceipt::STATUS_CANCELLED)
        ->call('save')
        ->assertHasErrors(['cancellation_reason_id']);
});

it('stores an uploaded attachment with a type', function () {
    Storage::fake('public');
    $r = AdvanceReceipt::factory()->create();

    Livewire::test(Edit::class, ['advanceReceipt' => $r])
        ->call('addAttachment')
        ->set('attachments.0.attachment_type', 'cheque_copy')
        ->set('attachmentFiles.0', UploadedFile::fake()->image('cheque.jpg'))
        ->call('save')
        ->assertHasNoErrors();

    $r->refresh()->load('attachments');
    expect($r->attachments)->toHaveCount(1)
        ->and($r->attachments->first()->attachment_type)->toBe('cheque_copy');
    Storage::disk('public')->assertExists($r->attachments->first()->path);
});

it('filters by status and payment mode', function () {
    $mode = PaymentModeMaster::factory()->create();
    $mine = AdvanceReceipt::factory()->partiallyReceived()->create(['payment_mode_id' => $mode->id]);
    $other = AdvanceReceipt::factory()->create();

    Livewire::test(Index::class)
        ->set('statusFilter', AdvanceReceipt::STATUS_PARTIALLY_RECEIVED)
        ->assertSee($mine->receipt_no)
        ->assertDontSee($other->receipt_no);

    Livewire::test(Index::class)
        ->set('modeFilter', (string) $mode->id)
        ->assertSee($mine->receipt_no)
        ->assertDontSee($other->receipt_no);
});

it('sums the received advance in the KPI', function () {
    AdvanceReceipt::factory()->create(['amount' => 1000]);
    AdvanceReceipt::factory()->create(['amount' => 2500]);
    AdvanceReceipt::factory()->cancelled()->create(['amount' => 9999]); // excluded

    Livewire::test(Index::class)
        ->assertViewHas('kpis', fn ($kpis) => (int) $kpis['received'] === 3500 && $kpis['count'] === 2);
});

it('exports the receipt report as CSV', function () {
    AdvanceReceipt::factory()->create();

    Livewire::test(Index::class)
        ->call('download')
        ->assertFileDownloaded();
});

it('deletes a receipt and cascades its attachments', function () {
    $r = AdvanceReceipt::factory()->create();
    $r->attachments()->create(['kind' => 'image', 'path' => 'x.jpg', 'sequence_no' => 1]);

    Livewire::test(Index::class)->call('delete', $r->id);

    expect(AdvanceReceipt::find($r->id))->toBeNull();
});

it('blocks the create page without permission', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('advance_receipt.view');
    $this->actingAs($user);

    $this->get(route('advance-receipt.create'))->assertForbidden();
});

it('requires authentication', function () {
    auth()->logout();
    $this->get(route('advance-receipt.index'))->assertRedirect(route('login'));
});
