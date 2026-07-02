<?php

use App\Modules\CounterSalesInvoice\Models\CounterSalesInvoice;
use App\Modules\CustomerMaster\Models\CustomerMaster;
use App\Modules\CustomerVehicleMaster\Models\CustomerVehicleMaster;
use App\Modules\Inventory\Models\StockEntry;
use App\Modules\Inventory\Services\StockLedger;
use App\Modules\RegularSalesInvoice\Models\RegularSalesInvoice;
use App\Modules\SalesReturn\Livewire\Edit;
use App\Modules\SalesReturn\Livewire\Index;
use App\Modules\SalesReturn\Models\SalesReturn;
use App\Modules\SpareMaster\Models\SpareMaster;
use App\Support\FinancialYear;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(adminUser());
});

it('renders the index page', function () {
    SalesReturn::factory()->count(2)->create();

    $this->get(route('sales-return.index'))
        ->assertOk()
        ->assertSeeLivewire(Index::class);
});

it('requires authentication', function () {
    auth()->logout();
    $this->get(route('sales-return.index'))->assertRedirect(route('login'));
});

it('stamps the correct series prefix per return type', function () {
    $customer = CustomerMaster::factory()->create();
    $fy = FinancialYear::label();

    $regular = SalesReturn::factory()->create(['return_type' => 'regular', 'customer_id' => $customer->id]);
    $insurance = SalesReturn::factory()->create(['return_type' => 'insurance', 'customer_id' => $customer->id]);
    $counter = SalesReturn::factory()->create(['return_type' => 'counter', 'customer_id' => $customer->id]);

    expect($regular->return_no)->toBe('MQ/SR/'.$fy.'/00001')
        ->and($insurance->return_no)->toBe('MQ/IR/'.$fy.'/00001')
        ->and($counter->return_no)->toBe('MQ/CR/'.$fy.'/00001');
});

it('numbers sequentially within a return type and financial year', function () {
    SalesReturn::factory()->create(['return_type' => 'regular']);
    SalesReturn::factory()->create(['return_type' => 'regular']);

    $fy = FinancialYear::label();
    $regulars = SalesReturn::where('return_type', 'regular')->orderBy('id')->get();
    expect($regulars[0]->return_no)->toBe('MQ/SR/'.$fy.'/00001')
        ->and($regulars[1]->return_no)->toBe('MQ/SR/'.$fy.'/00002');
});

it('creates a return and computes the return value and balance', function () {
    $customer = CustomerMaster::factory()->create();

    Livewire::test(Edit::class)
        ->set('customer_id', $customer->id)
        ->set('return_type', 'regular')
        ->set('sales_return_reason_id', null)
        ->set('refunded_amount', 100)
        ->set('items', [
            ['id' => null, 'line_type' => 'spare', 'spare_id' => null, 'labour_id' => null, 'uom_id' => null, 'tax_id' => null, 'description' => 'part', 'hsn_code' => null, 'qty' => 2, 'cost_rate' => 100, 'unit_rate' => 150, 'discount_value' => 0, 'tax_percent' => 18, 'sequence_no' => 1],
        ])
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect();

    $sr = SalesReturn::firstOrFail();
    // sell 300, tax 54, grand 354, balance 354-100=254
    expect((float) $sr->parts_total)->toBe(300.0)
        ->and((float) $sr->tax_total)->toBe(54.0)
        ->and((float) $sr->grand_total)->toBe(354.0)
        ->and((float) $sr->balance_refund)->toBe(254.0);
});

it('restores sold spares to stock and re-syncs on edit', function () {
    $spare = SpareMaster::factory()->create();
    StockEntry::create([
        'spare_id' => $spare->id, 'entry_type' => StockEntry::TYPE_OPENING, 'qty' => 100, 'rate_per_unit' => 50, 'moved_at' => now(),
    ]);
    $sr = SalesReturn::factory()->create();

    $component = Livewire::test(Edit::class, ['salesReturn' => $sr])
        ->set('items', [
            ['id' => null, 'line_type' => 'spare', 'spare_id' => $spare->id, 'labour_id' => null, 'uom_id' => null, 'tax_id' => null, 'description' => 'filter', 'hsn_code' => null, 'qty' => 10, 'cost_rate' => 30, 'unit_rate' => 50, 'discount_value' => 0, 'tax_percent' => 0, 'sequence_no' => 1],
        ])
        ->call('save')
        ->assertHasNoErrors();

    expect(StockLedger::currentQty($spare->id))->toBe(110.0); // 100 + 10 returned

    $component->set('items.0.qty', 4)->call('save')->assertHasNoErrors();
    expect(StockLedger::currentQty($spare->id))->toBe(104.0); // re-synced, not compounded
});

it('reverses the stock restore when the return is cancelled', function () {
    $spare = SpareMaster::factory()->create();
    StockEntry::create([
        'spare_id' => $spare->id, 'entry_type' => StockEntry::TYPE_OPENING, 'qty' => 100, 'rate_per_unit' => 50, 'moved_at' => now(),
    ]);
    $sr = SalesReturn::factory()->create();

    $component = Livewire::test(Edit::class, ['salesReturn' => $sr])
        ->set('items', [
            ['id' => null, 'line_type' => 'spare', 'spare_id' => $spare->id, 'labour_id' => null, 'uom_id' => null, 'tax_id' => null, 'description' => 'filter', 'hsn_code' => null, 'qty' => 10, 'cost_rate' => 30, 'unit_rate' => 50, 'discount_value' => 0, 'tax_percent' => 0, 'sequence_no' => 1],
        ])
        ->call('save')
        ->assertHasNoErrors();

    expect(StockLedger::currentQty($spare->id))->toBe(110.0);

    $component->set('status', 'cancelled')->call('save')->assertHasNoErrors();
    expect(StockLedger::currentQty($spare->id))->toBe(100.0) // restore reversed
        ->and(StockEntry::where('source_type', SalesReturn::class)->where('source_id', $sr->id)->count())->toBe(0);
});

it('converts a regular invoice into a return, snapshotting type and lines', function () {
    $customer = CustomerMaster::factory()->create();
    $vehicle = CustomerVehicleMaster::factory()->create(['customer_id' => $customer->id]);
    $inv = RegularSalesInvoice::factory()->create([
        'customer_id' => $customer->id,
        'customer_vehicle_id' => $vehicle->id,
        'invoice_type' => 'insurance',
    ]);
    $inv->items()->create([
        'line_type' => 'spare', 'description' => 'BRAKE PAD', 'qty' => 1, 'cost_rate' => 80, 'unit_rate' => 120, 'tax_percent' => 18, 'sequence_no' => 1,
    ]);

    $component = Livewire::withQueryParams(['from-regular-invoice' => $inv->id])->test(Edit::class);

    expect($component->get('regular_sales_invoice_id'))->toBe($inv->id)
        ->and($component->get('return_type'))->toBe('insurance') // insurance invoice → insurance return
        ->and($component->get('customer_id'))->toBe($customer->id)
        ->and($component->get('items'))->toHaveCount(1)
        ->and($component->get('items')[0]['description'])->toBe('BRAKE PAD');
});

it('converts a counter invoice into a counter return', function () {
    $customer = CustomerMaster::factory()->create();
    $inv = CounterSalesInvoice::factory()->create(['customer_id' => $customer->id]);
    $inv->items()->create([
        'line_type' => 'spare', 'description' => 'AIR FILTER', 'qty' => 2, 'cost_rate' => 40, 'unit_rate' => 60, 'tax_percent' => 18, 'sequence_no' => 1,
    ]);

    $component = Livewire::withQueryParams(['from-counter-invoice' => $inv->id])->test(Edit::class);

    expect($component->get('counter_sales_invoice_id'))->toBe($inv->id)
        ->and($component->get('return_type'))->toBe('counter')
        ->and($component->get('items'))->toHaveCount(1);
});

it('deletes a sales return from the index', function () {
    $sr = SalesReturn::factory()->create();

    Livewire::test(Index::class)->call('delete', $sr->id);

    expect(SalesReturn::find($sr->id))->toBeNull();
});
