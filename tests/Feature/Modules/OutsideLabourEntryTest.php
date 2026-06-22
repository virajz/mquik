<?php

use App\Modules\OutsideLabourEntry\Livewire\Edit;
use App\Modules\OutsideLabourEntry\Livewire\Index;
use App\Modules\OutsideLabourEntry\Models\OutsideLabourEntry;
use App\Modules\VendorMaster\Models\VendorMaster;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(adminUser());
});

it('renders the index page', function () {
    OutsideLabourEntry::factory()->count(2)->create();

    $this->get(route('outside-labour-entry.index'))
        ->assertOk()
        ->assertSeeLivewire(Index::class);
});

it('requires authentication', function () {
    auth()->logout();
    $this->get(route('outside-labour-entry.index'))->assertRedirect(route('login'));
});

it('creates an entry, stamps OLE number, and redirects into the editor', function () {
    $vendor = VendorMaster::factory()->create();

    Livewire::test(Edit::class)
        ->set('vendor_id', $vendor->id)
        ->set('invoice_no', 'inv-9001')
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect();

    $entry = OutsideLabourEntry::firstOrFail();
    expect($entry->entry_no)->toBe('OLE-'.str_pad((string) $entry->id, 5, '0', STR_PAD_LEFT))
        ->and($entry->invoice_no)->toBe('INV-9001');
});

it('computes parts, labour and tax totals from line items', function () {
    $entry = OutsideLabourEntry::factory()->create();

    Livewire::test(Edit::class, ['outsideLabourEntry' => $entry])
        ->set('items', [
            ['id' => null, 'line_type' => 'spare', 'spare_id' => null, 'labour_id' => null, 'tax_id' => null, 'description' => 'gasket', 'hsn_code' => null, 'qty' => 2, 'unit_rate' => 150, 'tax_percent' => 18, 'sequence_no' => 1],
            ['id' => null, 'line_type' => 'labour', 'spare_id' => null, 'labour_id' => null, 'tax_id' => null, 'description' => 'denting', 'hsn_code' => null, 'qty' => 1, 'unit_rate' => 1000, 'tax_percent' => 18, 'sequence_no' => 2],
        ])
        ->call('save')
        ->assertHasNoErrors();

    $entry->refresh();
    expect((float) $entry->parts_total)->toBe(300.0)
        ->and((float) $entry->labour_total)->toBe(1000.0)
        ->and((float) $entry->tax_total)->toBe(234.0)   // 18% of 1300
        ->and((float) $entry->grand_total)->toBe(1534.0);
});

it('deletes an entry from the index', function () {
    $entry = OutsideLabourEntry::factory()->create();

    Livewire::test(Index::class)->call('delete', $entry->id);

    expect(OutsideLabourEntry::find($entry->id))->toBeNull();
});
