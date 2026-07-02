<?php

use App\Modules\CustomerMaster\Models\CustomerMaster;
use App\Modules\RegularReceipt\Livewire\Edit;
use App\Modules\RegularReceipt\Livewire\Index;
use App\Modules\RegularReceipt\Models\RegularReceipt;
use App\Modules\RegularSalesInvoice\Models\RegularSalesInvoice;
use App\Support\FinancialYear;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(adminUser());
});

it('renders the index page', function () {
    RegularReceipt::factory()->count(2)->create();

    $this->get(route('regular-receipt.index'))
        ->assertOk()
        ->assertSeeLivewire(Index::class);
});

it('requires authentication', function () {
    auth()->logout();
    $this->get(route('regular-receipt.index'))->assertRedirect(route('login'));
});

it('creates a receipt, stamps the MQ/RR FY number, and redirects', function () {
    $customer = CustomerMaster::factory()->create();

    Livewire::test(Edit::class)
        ->set('customer_id', $customer->id)
        ->set('amount', 2500)
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect();

    $r = RegularReceipt::firstOrFail();
    $fy = FinancialYear::label();
    expect($r->receipt_no)->toBe('MQ/RR/'.$fy.'/00001')
        ->and($r->fy_label)->toBe($fy)
        ->and((float) $r->amount)->toBe(2500.0);
});

it('numbers sequentially within a financial year', function () {
    RegularReceipt::factory()->create();
    RegularReceipt::factory()->create();

    $fy = FinancialYear::label();
    $receipts = RegularReceipt::orderBy('id')->get();
    expect($receipts[0]->receipt_no)->toBe('MQ/RR/'.$fy.'/00001')
        ->and($receipts[1]->receipt_no)->toBe('MQ/RR/'.$fy.'/00002');
});

it('prefills customer, invoice link and balance-due amount from a regular invoice', function () {
    $customer = CustomerMaster::factory()->create();
    $inv = RegularSalesInvoice::factory()->create([
        'customer_id' => $customer->id,
        'balance_due' => 750,
    ]);

    $component = Livewire::withQueryParams(['from-regular-invoice' => $inv->id])->test(Edit::class);

    expect($component->get('regular_sales_invoice_id'))->toBe($inv->id)
        ->and($component->get('customer_id'))->toBe($customer->id)
        ->and((float) $component->get('amount'))->toBe(750.0);
});

it('stamps received_at when confirmed and cleared_at when the cheque clears', function () {
    $r = RegularReceipt::factory()->create();

    Livewire::test(Edit::class, ['regularReceipt' => $r])
        ->set('status', 'confirmed')
        ->set('cheque_no', 'CHQ001')
        ->set('cheque_status', 'cleared')
        ->call('save')
        ->assertHasNoErrors();

    $r->refresh();
    expect($r->received_at)->not->toBeNull()
        ->and($r->cleared_at)->not->toBeNull()
        ->and($r->cheque_no)->toBe('CHQ001');
});

it('stores an attachment tagged with its type', function () {
    Storage::fake('public');
    $r = RegularReceipt::factory()->create();

    Livewire::test(Edit::class, ['regularReceipt' => $r])
        ->set('attachmentType', 'cheque_copy')
        ->set('attachmentFiles.0', UploadedFile::fake()->image('cheque.jpg'))
        ->call('save')
        ->assertHasNoErrors();

    $r->refresh()->load('attachments');
    $att = $r->attachments->first();
    expect($att)->not->toBeNull()
        ->and($att->attachment_type)->toBe('cheque_copy');
    Storage::disk('public')->assertExists($att->path);
});

it('deletes a receipt from the index', function () {
    $r = RegularReceipt::factory()->create();

    Livewire::test(Index::class)->call('delete', $r->id);

    expect(RegularReceipt::find($r->id))->toBeNull();
});
