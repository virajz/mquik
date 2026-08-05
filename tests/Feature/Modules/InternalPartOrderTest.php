<?php

use App\Modules\InternalPartOrder\Livewire\Edit;
use App\Modules\InternalPartOrder\Livewire\Index;
use App\Modules\InternalPartOrder\Models\InternalPartOrder;
use App\Modules\Inventory\Models\StockEntry;
use App\Modules\Inventory\Services\StockIssuer;
use App\Modules\Inventory\Services\StockLedger;
use App\Modules\JobCard\Models\JobCard;
use App\Modules\PriorityMaster\Models\PriorityMaster;
use App\Modules\SpareMaster\Models\SpareMaster;
use App\Modules\UnitOfMeasureMaster\Models\UnitOfMeasureMaster;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(adminUser());
});

it('renders the index page', function () {
    InternalPartOrder::factory()->count(2)->create();

    $this->get(route('internal-part-order.index'))
        ->assertOk()
        ->assertSeeLivewire(Index::class);
});

it('requires authentication', function () {
    auth()->logout();
    $this->get(route('internal-part-order.index'))->assertRedirect(route('login'));
});

it('creates an IPO, stamps the number, and redirects into the editor', function () {
    $jobCard = JobCard::factory()->create();

    $urgent = PriorityMaster::factory()->create([
        'name' => 'URGENT', 'sort_order' => 30, 'applies_to' => 'both',
    ]);

    Livewire::test(Edit::class)
        ->set('job_card_id', $jobCard->id)
        ->set('ipo_type', 'general_use')
        ->set('priority_id', $urgent->id)
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect();

    $ipo = InternalPartOrder::firstOrFail();
    expect($ipo->order_no)->toBe('IPO-'.str_pad((string) $ipo->id, 5, '0', STR_PAD_LEFT))
        ->and($ipo->ipo_type)->toBe('general_use')
        ->and($ipo->priority->name)->toBe('URGENT');
});

it('prefills description, uom and stock status when a spare is picked', function () {
    $uom = UnitOfMeasureMaster::factory()->create();
    $spare = SpareMaster::factory()->create(['name' => 'BRAKE PAD SET', 'uom_id' => $uom->id]);
    $ipo = InternalPartOrder::factory()->create();

    $component = Livewire::test(Edit::class, ['internalPartOrder' => $ipo])
        ->call('addItem')
        ->set('items.0.spare_id', $spare->id);

    expect($component->get('items')[0]['description'])->toBe('BRAKE PAD SET')
        ->and($component->get('items')[0]['uom_id'])->toBe($uom->id)
        ->and($component->get('items')[0]['stock_status'])->toBe('out_of_stock'); // no stock entries
});

it('saves part lines with issue and stock status', function () {
    $ipo = InternalPartOrder::factory()->create();

    Livewire::test(Edit::class, ['internalPartOrder' => $ipo])
        ->set('status', 'partially_issued')
        ->set('items', [
            ['id' => null, 'spare_id' => null, 'uom_id' => null, 'return_type_id' => null, 'description' => 'brake pad', 'is_alternate' => false, 'qty_requested' => 4, 'qty_issued' => 2, 'qty_returned' => 0, 'stock_status' => 'available', 'issue_status' => 'partially_issued', 'return_status' => null, 'before_photo_path' => null, 'after_photo_path' => null, 'notes' => null, 'sequence_no' => 1],
        ])
        ->call('save')
        ->assertHasNoErrors();

    $ipo->refresh()->load('items');
    expect($ipo->status)->toBe('partially_issued')
        ->and($ipo->issued_at)->not->toBeNull()
        ->and($ipo->items)->toHaveCount(1)
        ->and($ipo->items[0]->description)->toBe('BRAKE PAD')
        ->and((float) $ipo->items[0]->qty_issued)->toBe(2.0)
        ->and($ipo->items[0]->issue_status)->toBe('partially_issued');
});

it('stamps approved_at when approval status becomes approved', function () {
    $ipo = InternalPartOrder::factory()->create();

    Livewire::test(Edit::class, ['internalPartOrder' => $ipo])
        ->set('approval_status', 'approved')
        ->call('save')
        ->assertHasNoErrors();

    expect($ipo->fresh()->approved_at)->not->toBeNull();
});

it('deletes an IPO from the index', function () {
    $ipo = InternalPartOrder::factory()->create();

    Livewire::test(Index::class)->call('delete', $ipo->id);

    expect(InternalPartOrder::find($ipo->id))->toBeNull();
});

it('deducts issued parts from stock and re-syncs on edit', function () {
    $spare = SpareMaster::factory()->create();
    StockIssuer::receive($spare->id, 20, 100, StockEntry::TYPE_OPENING);
    $ipo = InternalPartOrder::factory()->create();

    $component = Livewire::test(Edit::class, ['internalPartOrder' => $ipo])
        ->set('status', 'partially_issued')
        ->set('items', [ipoLine($spare->id, issued: 5)])
        ->call('save')
        ->assertHasNoErrors();

    expect(StockLedger::currentQty($spare->id))->toBe(15.0);

    // Editing corrects the ledger rather than stacking onto it.
    $component->set('items.0.qty_issued', 8)->call('save')->assertHasNoErrors();
    expect(StockLedger::currentQty($spare->id))->toBe(12.0);
});

it('refuses to issue more than the store holds', function () {
    $spare = SpareMaster::factory()->create(['name' => 'BRAKE PAD SET']);
    StockIssuer::receive($spare->id, 3, 100, StockEntry::TYPE_OPENING);
    $ipo = InternalPartOrder::factory()->create();

    Livewire::test(Edit::class, ['internalPartOrder' => $ipo])
        ->set('status', 'fully_issued')
        ->set('items', [ipoLine($spare->id, issued: 10)])
        ->call('save')
        ->assertHasErrors('items.0.qty_issued');

    // Rolled back whole — no partial issue landed.
    expect(StockLedger::currentQty($spare->id))->toBe(3.0)
        ->and($ipo->fresh()->items)->toHaveCount(0);
});

it('puts returned parts back at the rate they left at', function () {
    $spare = SpareMaster::factory()->create();
    StockIssuer::receive($spare->id, 10, 250, StockEntry::TYPE_OPENING);
    $ipo = InternalPartOrder::factory()->create();

    Livewire::test(Edit::class, ['internalPartOrder' => $ipo])
        ->set('status', 'fully_issued')
        ->set('items', [ipoLine($spare->id, issued: 6, returned: 2)])
        ->call('save')
        ->assertHasNoErrors();

    expect(StockLedger::currentQty($spare->id))->toBe(6.0); // 10 - 6 + 2

    $return = StockEntry::where('entry_type', StockEntry::TYPE_IPO_RETURN)->sole();
    expect((float) $return->rate_per_unit)->toBe(250.0);
});

it('holds no stock while the order is still a draft', function () {
    $spare = SpareMaster::factory()->create();
    StockIssuer::receive($spare->id, 10, 100, StockEntry::TYPE_OPENING);
    $ipo = InternalPartOrder::factory()->create();

    Livewire::test(Edit::class, ['internalPartOrder' => $ipo])
        ->set('status', 'draft')
        ->set('items', [ipoLine($spare->id, issued: 4)])
        ->call('save')
        ->assertHasNoErrors();

    expect(StockLedger::currentQty($spare->id))->toBe(10.0);
});

it('returns the parts to stock when the order is cancelled', function () {
    $spare = SpareMaster::factory()->create();
    StockIssuer::receive($spare->id, 10, 100, StockEntry::TYPE_OPENING);
    $ipo = InternalPartOrder::factory()->create();

    $component = Livewire::test(Edit::class, ['internalPartOrder' => $ipo])
        ->set('status', 'fully_issued')
        ->set('items', [ipoLine($spare->id, issued: 4)])
        ->call('save');
    expect(StockLedger::currentQty($spare->id))->toBe(6.0);

    $component->set('status', 'cancelled')->call('save')->assertHasNoErrors();
    expect(StockLedger::currentQty($spare->id))->toBe(10.0);
});

/** One IPO line in the component's array shape. */
function ipoLine(int $spareId, float $issued = 0, float $returned = 0): array
{
    return [
        'id' => null, 'spare_id' => $spareId, 'uom_id' => null, 'return_type_id' => null,
        'description' => 'part', 'is_alternate' => false, 'qty_requested' => max($issued, 1),
        'qty_issued' => $issued, 'qty_returned' => $returned, 'stock_status' => 'available',
        'issue_status' => 'issued', 'return_status' => null, 'before_photo_path' => null,
        'after_photo_path' => null, 'notes' => null, 'sequence_no' => 1,
    ];
}

it('previews the FIFO batch and remaining stock when a part is scanned', function () {
    $spare = SpareMaster::factory()->create(['name' => 'ENGINE OIL 5W30', 'spare_code' => 'OIL-5W30']);
    StockIssuer::receive($spare->id, 4, 400, StockEntry::TYPE_OPENING, null, [
        'batch_no' => 'B-SOON', 'expiry_date' => today()->addDays(20)->toDateString(),
    ]);
    StockIssuer::receive($spare->id, 6, 460, StockEntry::TYPE_PURCHASE, null, [
        'batch_no' => 'B-LATE', 'expiry_date' => today()->addYear()->toDateString(),
    ]);

    $component = Livewire::test(Edit::class, ['internalPartOrder' => InternalPartOrder::factory()->create()])
        ->set('scanCode', 'oil-5w30')
        ->call('scan')
        ->assertHasNoErrors();

    $scan = $component->get('pendingScan');
    expect($scan['spare_id'])->toBe($spare->id)
        ->and($scan['on_hand'])->toBe(10.0)
        ->and($scan['remaining'])->toBe(9.0)
        ->and($scan['short'])->toBeFalse()
        // Soonest expiry leaves first, and nothing has moved yet.
        ->and($scan['layers'][0]['batch_no'])->toBe('B-SOON')
        ->and(StockLedger::currentQty($spare->id))->toBe(10.0);
});

it('splits the preview across layers as the scanned quantity grows', function () {
    $spare = SpareMaster::factory()->create(['spare_code' => 'BP-1']);
    StockIssuer::receive($spare->id, 4, 100, StockEntry::TYPE_OPENING, null, ['batch_no' => 'A']);
    StockIssuer::receive($spare->id, 6, 160, StockEntry::TYPE_PURCHASE, null, ['batch_no' => 'B']);

    $component = Livewire::test(Edit::class, ['internalPartOrder' => InternalPartOrder::factory()->create()])
        ->set('scanCode', 'BP-1')
        ->call('scan')
        ->call('setScanQty', 7);

    $scan = $component->get('pendingScan');
    expect($scan['layers'])->toHaveCount(2)
        ->and($scan['layers'][0]['qty'])->toBe(4.0)
        ->and($scan['layers'][1]['qty'])->toBe(3.0)
        ->and($scan['remaining'])->toBe(3.0);
});

it('flags a scan that asks for more than is on hand and blocks confirming', function () {
    $spare = SpareMaster::factory()->create(['spare_code' => 'BP-2']);
    StockIssuer::receive($spare->id, 2, 100, StockEntry::TYPE_OPENING);

    $component = Livewire::test(Edit::class, ['internalPartOrder' => InternalPartOrder::factory()->create()])
        ->set('scanCode', 'BP-2')
        ->call('scan')
        ->call('setScanQty', 5);

    expect($component->get('pendingScan')['short'])->toBeTrue();
});

it('rejects a scan that matches no spare', function () {
    Livewire::test(Edit::class, ['internalPartOrder' => InternalPartOrder::factory()->create()])
        ->set('scanCode', 'NOSUCHPART')
        ->call('scan')
        ->assertHasErrors('scanCode');
});

it('adds a line on confirm and tops up the same line on a second scan', function () {
    $spare = SpareMaster::factory()->create(['spare_code' => 'BP-3']);
    StockIssuer::receive($spare->id, 20, 100, StockEntry::TYPE_OPENING);
    $ipo = InternalPartOrder::factory()->create();

    $component = Livewire::test(Edit::class, ['internalPartOrder' => $ipo])
        ->set('scanCode', 'BP-3')->call('scan')->call('confirmScan');

    expect($component->get('items'))->toHaveCount(1)
        ->and($component->get('items')[0]['qty_issued'])->toBe(1.0)
        ->and($component->get('pendingScan'))->toBeNull()
        ->and($component->get('scanCode'))->toBe('');

    // Same part again: tops up rather than adding a duplicate line.
    $component->set('scanCode', 'BP-3')->call('scan')->call('confirmScan');
    expect($component->get('items'))->toHaveCount(1)
        ->and($component->get('items')[0]['qty_issued'])->toBe(2.0);
});

it('moves the stock only when the order is saved, not when scanned', function () {
    $spare = SpareMaster::factory()->create(['spare_code' => 'BP-4']);
    StockIssuer::receive($spare->id, 10, 100, StockEntry::TYPE_OPENING);
    $ipo = InternalPartOrder::factory()->create();

    $component = Livewire::test(Edit::class, ['internalPartOrder' => $ipo])
        ->set('scanCode', 'BP-4')->call('scan')->call('setScanQty', 3)->call('confirmScan');

    expect(StockLedger::currentQty($spare->id))->toBe(10.0);   // nothing yet

    $component->set('status', 'fully_issued')->call('save')->assertHasNoErrors();
    expect(StockLedger::currentQty($spare->id))->toBe(7.0);
});
