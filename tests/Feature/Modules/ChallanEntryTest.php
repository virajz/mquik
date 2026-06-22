<?php

use App\Modules\ChallanEntry\Livewire\Edit;
use App\Modules\ChallanEntry\Livewire\Index;
use App\Modules\ChallanEntry\Models\Challan;
use App\Modules\ChargeTypeMaster\Models\ChargeTypeMaster;
use App\Modules\VendorMaster\Models\VendorMaster;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(adminUser());
});

it('renders the index page', function () {
    Challan::factory()->count(2)->create();

    $this->get(route('challan-entry.index'))
        ->assertOk()
        ->assertSeeLivewire(Index::class);
});

it('requires authentication', function () {
    auth()->logout();
    $this->get(route('challan-entry.index'))->assertRedirect(route('login'));
});

it('creates a challan, stamps CH number, and redirects into the editor', function () {
    $vendor = VendorMaster::factory()->create();

    Livewire::test(Edit::class)
        ->set('vendor_id', $vendor->id)
        ->set('purchase_type', 'emergency')
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect();

    $challan = Challan::firstOrFail();
    expect($challan->challan_no)->toBe('CH-'.str_pad((string) $challan->id, 5, '0', STR_PAD_LEFT))
        ->and($challan->purchase_type)->toBe('emergency');
});

it('computes line totals with discount, tax and additional charges', function () {
    $challan = Challan::factory()->create();
    $freight = ChargeTypeMaster::factory()->create(['name' => 'FREIGHT']);

    Livewire::test(Edit::class, ['challan' => $challan])
        ->set('items', [
            ['id' => null, 'spare_id' => null, 'uom_id' => null, 'tax_id' => null, 'rejection_reason_id' => null, 'description' => 'filter', 'hsn_code' => null, 'qty' => 10, 'unit_rate' => 100, 'discount_value' => 100, 'tax_percent' => 18, 'material_condition' => 'new', 'invoice_status' => 'received', 'sequence_no' => 1],
        ])
        ->set('charges', [
            ['id' => null, 'charge_type_id' => $freight->id, 'amount' => 50, 'notes' => null],
        ])
        ->call('save')
        ->assertHasNoErrors();

    $challan->refresh()->load(['items', 'charges']);
    // base 1000, discount 100, taxable 900, tax 162, charges 50 → grand 1112
    expect((float) $challan->parts_total)->toBe(1000.0)
        ->and((float) $challan->discount_total)->toBe(100.0)
        ->and((float) $challan->tax_total)->toBe(162.0)
        ->and((float) $challan->charges_total)->toBe(50.0)
        ->and((float) $challan->grand_total)->toBe(1112.0)
        ->and($challan->items)->toHaveCount(1)
        ->and($challan->charges)->toHaveCount(1);
});

it('deletes a challan from the index', function () {
    $challan = Challan::factory()->create();

    Livewire::test(Index::class)->call('delete', $challan->id);

    expect(Challan::find($challan->id))->toBeNull();
});
