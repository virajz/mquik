<?php

use App\Modules\Inventory\Models\StockEntry;
use App\Modules\Inventory\Services\StockLedger;
use App\Modules\PurchaseEntry\Livewire\Edit;
use App\Modules\PurchaseEntry\Livewire\Index;
use App\Modules\PurchaseEntry\Models\PurchaseEntry;
use App\Modules\SpareMaster\Models\SpareMaster;
use App\Modules\VendorMaster\Models\VendorMaster;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(adminUser());
});

it('renders the index page', function () {
    PurchaseEntry::factory()->count(2)->create();

    $this->get(route('purchase-entry.index'))
        ->assertOk()
        ->assertSeeLivewire(Index::class);
});

it('requires authentication', function () {
    auth()->logout();
    $this->get(route('purchase-entry.index'))->assertRedirect(route('login'));
});

it('creates a purchase, stamps PE number, and redirects into the editor', function () {
    $vendor = VendorMaster::factory()->create();

    Livewire::test(Edit::class)
        ->set('vendor_id', $vendor->id)
        ->set('invoice_type', 'e_invoice')
        ->set('invoice_no', 'inv-77')
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect();

    $pe = PurchaseEntry::firstOrFail();
    expect($pe->purchase_no)->toBe('PE-'.str_pad((string) $pe->id, 5, '0', STR_PAD_LEFT))
        ->and($pe->invoice_type)->toBe('e_invoice')
        ->and($pe->invoice_no)->toBe('INV-77');
});

it('posts spare-linked lines to stock and re-syncs on edit', function () {
    $spare = SpareMaster::factory()->create();
    $pe = PurchaseEntry::factory()->create();

    $component = Livewire::test(Edit::class, ['purchaseEntry' => $pe])
        ->set('items', [
            ['id' => null, 'spare_id' => $spare->id, 'uom_id' => null, 'tax_id' => null, 'rejection_reason_id' => null, 'description' => 'oil filter', 'hsn_code' => null, 'qty' => 5, 'unit_rate' => 200, 'discount_value' => 0, 'tax_percent' => 0, 'material_condition' => 'new', 'invoice_status' => 'received', 'sequence_no' => 1],
        ])
        ->call('save')
        ->assertHasNoErrors();

    expect(StockLedger::currentQty($spare->id))->toBe(5.0)
        ->and(StockEntry::where('source_type', PurchaseEntry::class)->where('source_id', $pe->id)->count())->toBe(1);

    // Re-save with a lower qty — stock should re-sync, not double-count.
    $component->set('items.0.qty', 3)->call('save')->assertHasNoErrors();

    expect(StockLedger::currentQty($spare->id))->toBe(3.0)
        ->and(StockEntry::where('source_type', PurchaseEntry::class)->where('source_id', $pe->id)->count())->toBe(1);
});

it('does not post free-text (no spare) lines to stock', function () {
    $pe = PurchaseEntry::factory()->create();

    Livewire::test(Edit::class, ['purchaseEntry' => $pe])
        ->set('items', [
            ['id' => null, 'spare_id' => null, 'uom_id' => null, 'tax_id' => null, 'rejection_reason_id' => null, 'description' => 'misc item', 'hsn_code' => null, 'qty' => 4, 'unit_rate' => 100, 'discount_value' => 0, 'tax_percent' => 0, 'material_condition' => 'new', 'invoice_status' => 'received', 'sequence_no' => 1],
        ])
        ->call('save')
        ->assertHasNoErrors();

    expect(StockEntry::where('source_type', PurchaseEntry::class)->where('source_id', $pe->id)->count())->toBe(0);
});

it('deletes a purchase from the index', function () {
    $pe = PurchaseEntry::factory()->create();

    Livewire::test(Index::class)->call('delete', $pe->id);

    expect(PurchaseEntry::find($pe->id))->toBeNull();
});
