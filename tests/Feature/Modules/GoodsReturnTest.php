<?php

use App\Modules\GoodsReturn\Livewire\Edit;
use App\Modules\GoodsReturn\Livewire\Index;
use App\Modules\GoodsReturn\Models\GoodsReturn;
use App\Modules\Inventory\Models\StockEntry;
use App\Modules\Inventory\Services\StockLedger;
use App\Modules\SpareMaster\Models\SpareMaster;
use App\Modules\VendorMaster\Models\VendorMaster;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(adminUser());
});

it('renders the index page', function () {
    GoodsReturn::factory()->count(2)->create();

    $this->get(route('goods-return.index'))
        ->assertOk()
        ->assertSeeLivewire(Index::class);
});

it('requires authentication', function () {
    auth()->logout();
    $this->get(route('goods-return.index'))->assertRedirect(route('login'));
});

it('creates a goods return, stamps GR number, and redirects', function () {
    $vendor = VendorMaster::factory()->create();

    Livewire::test(Edit::class)
        ->set('vendor_id', $vendor->id)
        ->set('document_type', 'debit_note')
        ->set('grn_reference', 'grn-5')
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect();

    $gr = GoodsReturn::firstOrFail();
    expect($gr->return_no)->toBe('GR-'.str_pad((string) $gr->id, 5, '0', STR_PAD_LEFT))
        ->and($gr->document_type)->toBe('debit_note')
        ->and($gr->grn_reference)->toBe('GRN-5');
});

it('reduces stock for returned spare lines and re-syncs on edit', function () {
    $spare = SpareMaster::factory()->create();
    StockEntry::create([
        'spare_id' => $spare->id, 'entry_type' => StockEntry::TYPE_OPENING, 'qty' => 50, 'rate_per_unit' => 20, 'moved_at' => now(),
    ]);
    $gr = GoodsReturn::factory()->create();

    $component = Livewire::test(Edit::class, ['goodsReturn' => $gr])
        ->set('items', [
            ['id' => null, 'spare_id' => $spare->id, 'uom_id' => null, 'tax_id' => null, 'description' => 'part', 'hsn_code' => null, 'qty' => 8, 'unit_rate' => 20, 'tax_percent' => 0, 'material_condition' => 'unused', 'sequence_no' => 1],
        ])
        ->call('save')
        ->assertHasNoErrors();

    expect(StockLedger::currentQty($spare->id))->toBe(42.0); // 50 - 8 returned

    $component->set('items.0.qty', 5)->call('save')->assertHasNoErrors();
    expect(StockLedger::currentQty($spare->id))->toBe(45.0); // 50 - 5 (re-synced)
});

it('deletes a goods return from the index', function () {
    $gr = GoodsReturn::factory()->create();

    Livewire::test(Index::class)->call('delete', $gr->id);

    expect(GoodsReturn::find($gr->id))->toBeNull();
});
