<?php

use App\Modules\CustomerMaster\Models\CustomerMaster;
use App\Modules\ReceiptRefund\Livewire\Edit;
use App\Modules\ReceiptRefund\Livewire\Index;
use App\Modules\ReceiptRefund\Models\ReceiptRefund;
use App\Modules\RegularReceipt\Models\RegularReceipt;
use App\Support\FinancialYear;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(adminUser());
});

it('renders the index page', function () {
    ReceiptRefund::factory()->count(2)->create();

    $this->get(route('receipt-refund.index'))
        ->assertOk()
        ->assertSeeLivewire(Index::class);
});

it('requires authentication', function () {
    auth()->logout();
    $this->get(route('receipt-refund.index'))->assertRedirect(route('login'));
});

it('creates a refund, stamps the MQ/RF FY number, and redirects', function () {
    $customer = CustomerMaster::factory()->create();

    Livewire::test(Edit::class)
        ->set('customer_id', $customer->id)
        ->set('refund_against', 'regular_receipt')
        ->set('amount', 900)
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect();

    $r = ReceiptRefund::firstOrFail();
    $fy = FinancialYear::label();
    expect($r->refund_no)->toBe('MQ/RF/'.$fy.'/00001')
        ->and($r->fy_label)->toBe($fy)
        ->and((float) $r->amount)->toBe(900.0);
});

it('prefills customer and amount from a regular receipt', function () {
    $customer = CustomerMaster::factory()->create();
    $receipt = RegularReceipt::factory()->create(['customer_id' => $customer->id, 'amount' => 1200]);

    $component = Livewire::withQueryParams(['from-receipt' => $receipt->id])->test(Edit::class);

    expect($component->get('regular_receipt_id'))->toBe($receipt->id)
        ->and($component->get('customer_id'))->toBe($customer->id)
        ->and((float) $component->get('amount'))->toBe(1200.0);
});

it('stamps refunded_at when marked refunded', function () {
    $r = ReceiptRefund::factory()->create();

    Livewire::test(Edit::class, ['receiptRefund' => $r])
        ->set('refund_status', 'refunded')
        ->call('save')
        ->assertHasNoErrors();

    expect($r->refresh()->refunded_at)->not->toBeNull();
});

it('stores an attachment tagged with its type', function () {
    Storage::fake('public');
    $r = ReceiptRefund::factory()->create();

    Livewire::test(Edit::class, ['receiptRefund' => $r])
        ->set('attachmentType', 'customer_request_proof')
        ->set('attachmentFiles.0', UploadedFile::fake()->create('proof.pdf', 100, 'application/pdf'))
        ->call('save')
        ->assertHasNoErrors();

    $att = $r->refresh()->attachments->first();
    expect($att)->not->toBeNull()
        ->and($att->attachment_type)->toBe('customer_request_proof');
    Storage::disk('public')->assertExists($att->path);
});

it('deletes a refund from the index', function () {
    $r = ReceiptRefund::factory()->create();

    Livewire::test(Index::class)->call('delete', $r->id);

    expect(ReceiptRefund::find($r->id))->toBeNull();
});
