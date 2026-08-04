<?php

use App\Modules\Inventory\Exceptions\InsufficientStockException;
use App\Modules\Inventory\Models\StockEntry;
use App\Modules\Inventory\Services\StockIssuer;
use App\Modules\Inventory\Services\StockLedger;
use App\Modules\SpareMaster\Models\SpareMaster;

/**
 * The FIFO allocator every stock movement goes through: cost-of-issue, the
 * negative-stock guard, and batch/expiry traceability all come out of it.
 */
beforeEach(function () {
    $this->actingAs(adminUser());
    $this->spare = SpareMaster::factory()->create(['name' => 'ENGINE OIL 5W30']);
});

it('consumes layers oldest-first and charges each at its own rate', function () {
    StockIssuer::receive($this->spare->id, 4, 100, StockEntry::TYPE_PURCHASE, null, ['moved_at' => now()->subDays(10)]);
    StockIssuer::receive($this->spare->id, 6, 150, StockEntry::TYPE_PURCHASE, null, ['moved_at' => now()->subDays(2)]);

    $entries = StockIssuer::issue($this->spare->id, 7, StockEntry::TYPE_CONSUMPTION);

    // 4 off the ₹100 layer, 3 off the ₹150 layer — two entries, not one average.
    expect($entries)->toHaveCount(2)
        ->and((float) $entries[0]->qty)->toBe(-4.0)
        ->and((float) $entries[0]->rate_per_unit)->toBe(100.0)
        ->and((float) $entries[1]->qty)->toBe(-3.0)
        ->and((float) $entries[1]->rate_per_unit)->toBe(150.0)
        ->and(StockLedger::currentQty($this->spare->id))->toBe(3.0);
});

it('refuses to issue more than is on hand', function () {
    StockIssuer::receive($this->spare->id, 5, 100, StockEntry::TYPE_PURCHASE);

    expect(fn () => StockIssuer::issue($this->spare->id, 8, StockEntry::TYPE_CONSUMPTION))
        ->toThrow(InsufficientStockException::class, 'asked for 8 but only 5 in stock');

    // Nothing was written — the guard fires before any entry lands.
    expect(StockLedger::currentQty($this->spare->id))->toBe(5.0);
});

it('allows going negative when the caller opts in, valued at the last inward rate', function () {
    StockIssuer::receive($this->spare->id, 5, 120, StockEntry::TYPE_PURCHASE);

    $entries = StockIssuer::issue($this->spare->id, 8, StockEntry::TYPE_CONSUMPTION, null, ['allow_negative' => true]);

    expect($entries)->toHaveCount(2)
        ->and((float) $entries[1]->qty)->toBe(-3.0)
        ->and((float) $entries[1]->rate_per_unit)->toBe(120.0)
        ->and($entries[1]->layer_id)->toBeNull()
        ->and(StockLedger::currentQty($this->spare->id))->toBe(-3.0);
});

it('honours the config default for negative stock', function () {
    config()->set('inventory.block_negative_stock', false);

    StockIssuer::issue($this->spare->id, 2, StockEntry::TYPE_CONSUMPTION);

    expect(StockLedger::currentQty($this->spare->id))->toBe(-2.0);
});

it('issues the soonest-expiring batch first, not the oldest receipt', function () {
    // Received earlier, but expires later — must NOT go out first.
    StockIssuer::receive($this->spare->id, 5, 100, StockEntry::TYPE_PURCHASE, null, [
        'moved_at' => now()->subDays(30), 'batch_no' => 'B-LATE', 'expiry_date' => today()->addYear()->toDateString(),
    ]);
    StockIssuer::receive($this->spare->id, 5, 110, StockEntry::TYPE_PURCHASE, null, [
        'moved_at' => now()->subDays(2), 'batch_no' => 'B-SOON', 'expiry_date' => today()->addDays(20)->toDateString(),
    ]);

    $entries = StockIssuer::issue($this->spare->id, 6, StockEntry::TYPE_IPO_ISSUE);

    expect($entries[0]->batch_no)->toBe('B-SOON')
        ->and((float) $entries[0]->qty)->toBe(-5.0)
        ->and($entries[1]->batch_no)->toBe('B-LATE')
        ->and((float) $entries[1]->qty)->toBe(-1.0);
});

it('traces an issue back to the layer it came from', function () {
    $layer = StockIssuer::receive($this->spare->id, 10, 100, StockEntry::TYPE_PURCHASE, null, ['batch_no' => 'lot-9']);

    $entries = StockIssuer::issue($this->spare->id, 4, StockEntry::TYPE_IPO_ISSUE);

    expect($entries[0]->layer_id)->toBe($layer->id)
        ->and($entries[0]->batch_no)->toBe('LOT-9')   // uppercased on receipt
        ->and($layer->batch_no)->toBe('LOT-9');
});

it('reports on-hand quantity per batch', function () {
    StockIssuer::receive($this->spare->id, 10, 100, StockEntry::TYPE_PURCHASE, null, ['batch_no' => 'A', 'expiry_date' => today()->addDays(10)->toDateString()]);
    StockIssuer::receive($this->spare->id, 10, 100, StockEntry::TYPE_PURCHASE, null, ['batch_no' => 'B', 'expiry_date' => today()->addDays(60)->toDateString()]);
    StockIssuer::issue($this->spare->id, 4, StockEntry::TYPE_CONSUMPTION);

    $balances = StockLedger::batchBalances($this->spare->id);

    expect($balances)->toHaveCount(2)
        ->and($balances[0]['batch_no'])->toBe('A')
        ->and($balances[0]['qty'])->toBe(6.0)
        ->and($balances[1]['qty'])->toBe(10.0);
});

it('drops a fully drawn-down layer out of the open list', function () {
    StockIssuer::receive($this->spare->id, 5, 100, StockEntry::TYPE_PURCHASE, null, ['batch_no' => 'A']);
    StockIssuer::receive($this->spare->id, 5, 100, StockEntry::TYPE_PURCHASE, null, ['batch_no' => 'B']);
    StockIssuer::issue($this->spare->id, 5, StockEntry::TYPE_CONSUMPTION);

    $layers = StockLedger::openLayers($this->spare->id);
    expect($layers)->toHaveCount(1)
        ->and($layers[0]['batch_no'])->toBe('B');
});

it('flags batches expiring inside the warning window, lapsed ones first', function () {
    StockIssuer::receive($this->spare->id, 5, 100, StockEntry::TYPE_PURCHASE, null, ['batch_no' => 'LAPSED', 'expiry_date' => today()->subDays(5)->toDateString()]);
    StockIssuer::receive($this->spare->id, 5, 100, StockEntry::TYPE_PURCHASE, null, ['batch_no' => 'SOON', 'expiry_date' => today()->addDays(30)->toDateString()]);
    StockIssuer::receive($this->spare->id, 5, 100, StockEntry::TYPE_PURCHASE, null, ['batch_no' => 'FINE', 'expiry_date' => today()->addYears(2)->toDateString()]);

    $flagged = StockLedger::expiringBatches(90);

    expect($flagged->pluck('batch_no')->all())->toBe(['LAPSED', 'SOON']);
});

it('leaves an exhausted batch out of the expiry alert', function () {
    StockIssuer::receive($this->spare->id, 5, 100, StockEntry::TYPE_PURCHASE, null, ['batch_no' => 'GONE', 'expiry_date' => today()->addDays(3)->toDateString()]);
    StockIssuer::issue($this->spare->id, 5, StockEntry::TYPE_CONSUMPTION);

    expect(StockLedger::expiringBatches(90))->toBeEmpty();
});

it('adjusts stock up as a new layer and down through the layers', function () {
    StockIssuer::receive($this->spare->id, 10, 200, StockEntry::TYPE_PURCHASE);

    StockIssuer::adjust($this->spare->id, 5);
    expect(StockLedger::currentQty($this->spare->id))->toBe(15.0);

    StockIssuer::adjust($this->spare->id, -12);
    expect(StockLedger::currentQty($this->spare->id))->toBe(3.0);

    // A count that finds less than the ledger holds must land even if it
    // takes stock negative — the shelf is the authority.
    StockIssuer::adjust($this->spare->id, -10);
    expect(StockLedger::currentQty($this->spare->id))->toBe(-7.0);
});

it('reverses everything a document posted so a re-save cannot double-count', function () {
    $source = SpareMaster::factory()->create();   // stands in for any document
    StockIssuer::receive($this->spare->id, 10, 100, StockEntry::TYPE_PURCHASE, $source);
    expect(StockLedger::currentQty($this->spare->id))->toBe(10.0);

    StockIssuer::reverse($source);
    expect(StockLedger::currentQty($this->spare->id))->toBe(0.0);
});

it('ignores zero and negative quantities', function () {
    expect(StockIssuer::receive($this->spare->id, 0, 100, StockEntry::TYPE_PURCHASE))->toBeNull()
        ->and(StockIssuer::issue($this->spare->id, 0, StockEntry::TYPE_CONSUMPTION))->toBe([])
        ->and(StockIssuer::adjust($this->spare->id, 0))->toBe([])
        ->and(StockEntry::count())->toBe(0);
});
