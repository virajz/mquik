<?php

use App\Modules\CounterSalesInvoice\Livewire\Edit;
use App\Modules\CounterSalesInvoice\Livewire\Index;
use App\Modules\CounterSalesInvoice\Models\CounterSalesInvoice;
use App\Modules\CustomerMaster\Models\CustomerMaster;
use App\Modules\Inventory\Models\StockEntry;
use App\Modules\Inventory\Services\StockLedger;
use App\Modules\SpareMaster\Models\SpareMaster;
use App\Support\FinancialYear;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(adminUser());
});

it('renders the index page', function () {
    CounterSalesInvoice::factory()->count(2)->create();

    $this->get(route('counter-sales-invoice.index'))
        ->assertOk()
        ->assertSeeLivewire(Index::class);
});

it('requires authentication', function () {
    auth()->logout();
    $this->get(route('counter-sales-invoice.index'))->assertRedirect(route('login'));
});

it('creates a counter invoice, stamps the MQ/CS FY number, and redirects', function () {
    $customer = CustomerMaster::factory()->create();

    Livewire::test(Edit::class)
        ->set('customer_id', $customer->id)
        ->set('delivery_type', 'courier')
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect();

    $inv = CounterSalesInvoice::firstOrFail();
    $fy = FinancialYear::label();
    expect($inv->invoice_no)->toBe('MQ/CS/'.$fy.'/00001')
        ->and($inv->fy_label)->toBe($fy)
        ->and($inv->customer_id)->toBe($customer->id)
        ->and($inv->delivery_type)->toBe('courier');
});

it('computes gross margin from line items', function () {
    $inv = CounterSalesInvoice::factory()->create();

    Livewire::test(Edit::class, ['counterSalesInvoice' => $inv])
        ->set('items', [
            ['id' => null, 'line_type' => 'spare', 'spare_id' => null, 'labour_id' => null, 'uom_id' => null, 'tax_id' => null, 'description' => 'part', 'hsn_code' => null, 'qty' => 2, 'cost_rate' => 100, 'unit_rate' => 150, 'discount_value' => 0, 'tax_percent' => 18, 'sequence_no' => 1],
        ])
        ->call('save')
        ->assertHasNoErrors();

    $inv->refresh();
    expect((float) $inv->parts_total)->toBe(300.0)
        ->and((float) $inv->cost_total)->toBe(200.0)
        ->and((float) $inv->tax_total)->toBe(54.0)
        ->and((float) $inv->grand_total)->toBe(354.0)
        ->and((float) $inv->profit_total)->toBe(100.0)
        ->and((float) $inv->margin_percent)->toBe(33.33);
});

it('deducts sold spares from stock and re-syncs on edit', function () {
    $spare = SpareMaster::factory()->create();
    StockEntry::create([
        'spare_id' => $spare->id, 'entry_type' => StockEntry::TYPE_OPENING, 'qty' => 100, 'rate_per_unit' => 50, 'moved_at' => now(),
    ]);
    $inv = CounterSalesInvoice::factory()->create();

    $component = Livewire::test(Edit::class, ['counterSalesInvoice' => $inv])
        ->set('items', [
            ['id' => null, 'line_type' => 'spare', 'spare_id' => $spare->id, 'labour_id' => null, 'uom_id' => null, 'tax_id' => null, 'description' => 'filter', 'hsn_code' => null, 'qty' => 10, 'cost_rate' => 30, 'unit_rate' => 50, 'discount_value' => 0, 'tax_percent' => 0, 'sequence_no' => 1],
        ])
        ->call('save')
        ->assertHasNoErrors();

    expect(StockLedger::currentQty($spare->id))->toBe(90.0); // 100 - 10 sold

    $component->set('items.0.qty', 4)->call('save')->assertHasNoErrors();
    expect(StockLedger::currentQty($spare->id))->toBe(96.0); // re-synced, not compounded
});

it('reverses the stock movement when the invoice is cancelled', function () {
    $spare = SpareMaster::factory()->create();
    StockEntry::create([
        'spare_id' => $spare->id, 'entry_type' => StockEntry::TYPE_OPENING, 'qty' => 100, 'rate_per_unit' => 50, 'moved_at' => now(),
    ]);
    $inv = CounterSalesInvoice::factory()->create();

    $component = Livewire::test(Edit::class, ['counterSalesInvoice' => $inv])
        ->set('items', [
            ['id' => null, 'line_type' => 'spare', 'spare_id' => $spare->id, 'labour_id' => null, 'uom_id' => null, 'tax_id' => null, 'description' => 'filter', 'hsn_code' => null, 'qty' => 10, 'cost_rate' => 30, 'unit_rate' => 50, 'discount_value' => 0, 'tax_percent' => 0, 'sequence_no' => 1],
        ])
        ->call('save')
        ->assertHasNoErrors();

    expect(StockLedger::currentQty($spare->id))->toBe(90.0);

    $component->set('status', 'cancelled')->call('save')->assertHasNoErrors();
    expect(StockLedger::currentQty($spare->id))->toBe(100.0) // stock returned
        ->and(StockEntry::where('source_type', CounterSalesInvoice::class)->where('source_id', $inv->id)->count())->toBe(0);
});

it('does not deduct labour lines from stock', function () {
    $inv = CounterSalesInvoice::factory()->create();

    Livewire::test(Edit::class, ['counterSalesInvoice' => $inv])
        ->set('items', [
            ['id' => null, 'line_type' => 'labour', 'spare_id' => null, 'labour_id' => null, 'uom_id' => null, 'tax_id' => null, 'description' => 'fitting', 'hsn_code' => null, 'qty' => 3, 'cost_rate' => 0, 'unit_rate' => 100, 'discount_value' => 0, 'tax_percent' => 0, 'sequence_no' => 1],
        ])
        ->call('save')
        ->assertHasNoErrors();

    expect(StockEntry::where('source_type', CounterSalesInvoice::class)->where('source_id', $inv->id)->count())->toBe(0);
});

it('deletes a counter invoice from the index', function () {
    $inv = CounterSalesInvoice::factory()->create();

    Livewire::test(Index::class)->call('delete', $inv->id);

    expect(CounterSalesInvoice::find($inv->id))->toBeNull();
});
