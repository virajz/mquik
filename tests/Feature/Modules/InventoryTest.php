<?php

use App\Models\User;
use App\Modules\Inventory\Livewire\Index;
use App\Modules\Inventory\Models\StockEntry;
use App\Modules\Inventory\Services\StockLedger;
use App\Modules\SpareMaster\Models\SpareMaster;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(adminUser());
});

it('renders the inventory index page', function () {
    $this->get(route('inventory.index'))
        ->assertOk()
        ->assertSeeLivewire(Index::class);
});

it('requires authentication', function () {
    auth()->logout();

    $this->get(route('inventory.index'))->assertRedirect(route('login'));
});

it('denies access without permission', function () {
    $this->actingAs(User::factory()->create());

    $this->get(route('inventory.index'))->assertForbidden();
});

it('StockLedger::currentQty returns zero when no entries exist', function () {
    $spare = SpareMaster::factory()->create();

    expect(StockLedger::currentQty($spare->id))->toBe(0.0);
});

it('StockLedger::currentQty sums signed entries correctly', function () {
    $spare = SpareMaster::factory()->create();

    StockEntry::factory()->in(10)->create(['spare_id' => $spare->id]);
    StockEntry::factory()->out(3)->create(['spare_id' => $spare->id]);

    expect(StockLedger::currentQty($spare->id))->toBe(7.0);
});

it('StockLedger::currentQtyMap returns bulk qty correctly', function () {
    $s1 = SpareMaster::factory()->create();
    $s2 = SpareMaster::factory()->create();

    StockEntry::factory()->in(20)->create(['spare_id' => $s1->id]);
    StockEntry::factory()->in(5)->create(['spare_id' => $s2->id]);
    StockEntry::factory()->out(2)->create(['spare_id' => $s2->id]);

    $map = StockLedger::currentQtyMap([$s1->id, $s2->id]);

    expect($map[$s1->id])->toBe(20.0)
        ->and($map[$s2->id])->toBe(3.0);
});

it('StockLedger::alertStatus returns correct status', function () {
    expect(StockLedger::alertStatus(0.0, 5.0, 50.0))->toBe('zero')
        ->and(StockLedger::alertStatus(-1.0, 5.0, 50.0))->toBe('negative')
        ->and(StockLedger::alertStatus(3.0, 5.0, 50.0))->toBe('below_min')
        ->and(StockLedger::alertStatus(60.0, 5.0, 50.0))->toBe('above_max')
        ->and(StockLedger::alertStatus(20.0, 5.0, 50.0))->toBe('ok');
});

it('StockLedger::snapshot returns all fields', function () {
    $spare = SpareMaster::factory()->create(['min_qty' => 5, 'max_qty' => 50, 'location' => 'SHELF-A']);

    StockEntry::factory()->in(25)->create(['spare_id' => $spare->id, 'rate_per_unit' => 100]);

    $snap = StockLedger::snapshot($spare);

    expect($snap['current_qty'])->toBe(25.0)
        ->and($snap['alert'])->toBe('ok')
        ->and($snap['location'])->toBe('SHELF-A')
        ->and($snap['avg_rate'])->toBe(100.0);
});

it('Index shows spares and their qty on hand', function () {
    $spare = SpareMaster::factory()->create(['name' => 'BRAKE PAD']);
    StockEntry::factory()->in(10)->create(['spare_id' => $spare->id]);

    Livewire::test(Index::class)
        ->assertSee('BRAKE PAD')
        ->assertSee('10.00');
});

it('Index filters by spare name search', function () {
    $s1 = SpareMaster::factory()->create(['name' => 'OIL FILTER']);
    $s2 = SpareMaster::factory()->create(['name' => 'BRAKE PAD']);

    Livewire::test(Index::class)
        ->set('search', 'OIL')
        ->assertSee('OIL FILTER')
        ->assertDontSee('BRAKE PAD');
});

it('StockEntry::record creates an entry with actor', function () {
    $spare = SpareMaster::factory()->create();

    StockEntry::record(
        spareId: $spare->id,
        entryType: StockEntry::TYPE_OPENING,
        qty: 100,
        ratePerUnit: 250,
        notes: 'Opening stock',
    );

    $entry = StockEntry::first();
    expect($entry->spare_id)->toBe($spare->id)
        ->and($entry->entry_type)->toBe(StockEntry::TYPE_OPENING)
        ->and((float) $entry->qty)->toBe(100.0)
        ->and($entry->notes)->toBe('Opening stock');
});
