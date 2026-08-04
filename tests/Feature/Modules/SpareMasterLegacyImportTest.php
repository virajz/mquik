<?php

use App\Modules\HsnMaster\Models\HsnMaster;
use App\Modules\InventoryGroupMaster\Models\InventoryGroupMaster;
use App\Modules\SpareBrandMaster\Models\SpareBrandMaster;
use App\Modules\SpareMaster\Models\SpareMaster;
use App\Modules\SpareMaster\Models\SpareRateHistory;
use App\Modules\TaxMaster\Models\TaxMaster;
use App\Modules\UnitOfMeasureMaster\Models\UnitOfMeasureMaster;
use App\Modules\WorkshopDepartmentMaster\Models\WorkshopDepartmentMaster;
use Database\Seeders\MasterDataSeeder;
use Illuminate\Support\Facades\File;

/**
 * Covers the old-ERP spare import: how the flat CSV collapses into spares plus
 * dated rate revisions, and how it handles the legacy data's dirt (reused part
 * numbers, junk HSN codes, blank part numbers).
 */

/** The header of the client's export, verbatim — including its UTF-8 BOM. */
const SPARE_HEADER = "\xEF\xBB\xBFPartName,PartDescription,HSNACSNo,PartNo,Ref_PartNo,UOM,MadeIn,Company,Rate,TotalAmount,VINNo,Remarks,InvGroupName,InvSubGroupName,Service,Vat,AVat,WEF,InventoryName,IsMultiBarCode,Location,ReorderQty,MaxQty,SpareCategory,DepartmentName,SPCategory,VendorBarcode,MainDepartmentName";

/**
 * Build one CSV line in the export's column order.
 *
 * @param  array<string, string>  $overrides
 */
function spareRow(array $overrides = []): string
{
    $row = array_merge([
        'PartName' => 'ARM SUSP LOWER FR RH', 'PartDescription' => '', 'HSNACSNo' => '87089900',
        'PartNo' => '48068-0D081', 'Ref_PartNo' => 'NULL', 'UOM' => 'PCS', 'MadeIn' => 'NULL',
        'Company' => 'TOYOTA', 'Rate' => '1000', 'TotalAmount' => '1180', 'VINNo' => 'NULL',
        'Remarks' => '', 'InvGroupName' => 'SUSPENSION', 'InvSubGroupName' => 'LOWER ARM',
        'Service' => '9', 'Vat' => '9', 'AVat' => '0', 'WEF' => '01/02/21', 'InventoryName' => 'X',
        'IsMultiBarCode' => '1', 'Location' => '', 'ReorderQty' => '0', 'MaxQty' => '0',
        'SpareCategory' => '1', 'DepartmentName' => 'MECHANICAL', 'SPCategory' => 'FAST MOVING',
        'VendorBarcode' => 'NULL', 'MainDepartmentName' => 'Mechanical',
    ], $overrides);

    return implode(',', array_map(fn ($v) => str_contains($v, ',') ? '"'.$v.'"' : $v, $row));
}

/**
 * Write the rows to a scratch Spare.csv and run just the spare step against it.
 *
 * @param  list<string>  $rows
 */
function importSpares(array $rows): void
{
    $dir = storage_path('framework/testing/master-data-'.uniqid());
    File::ensureDirectoryExists($dir);
    File::put($dir.'/Spare.csv', SPARE_HEADER."\n".implode("\n", $rows)."\n");

    (new MasterDataSeeder)->importSpares($dir);

    File::deleteDirectory($dir);
}

beforeEach(function () {
    TaxMaster::factory()->create(['name' => 'GST 18%', 'gst_percent' => 18]);
    TaxMaster::factory()->create(['name' => 'GST 5%', 'gst_percent' => 5]);
    WorkshopDepartmentMaster::factory()->create(['name' => 'SERVICE']);
    WorkshopDepartmentMaster::factory()->create(['name' => 'DETAILING']);
    UnitOfMeasureMaster::factory()->create(['name' => 'PIECES', 'code' => 'PCS']);
});

it('collapses repeated part numbers into one spare carrying the newest rate', function () {
    importSpares([
        spareRow(['Rate' => '1540.62', 'TotalAmount' => '1817.93', 'WEF' => '01/02/21']),
        spareRow(['Rate' => '1773.44', 'TotalAmount' => '2092.66', 'WEF' => '07/07/25']),
        spareRow(['Rate' => '1568.36', 'TotalAmount' => '1850.66', 'WEF' => '19/02/22']),
    ]);

    $spare = SpareMaster::where('spare_code', '48068-0D081')->sole();

    expect(SpareMaster::count())->toBe(1)
        ->and((float) $spare->rate_before_tax)->toBe(1773.44)
        ->and((float) $spare->mrp)->toBe(2092.66);

    // Newest first, every revision kept — including the one on the spare.
    expect($spare->rateHistory->pluck('rate_before_tax')->map(fn ($r) => (float) $r)->all())
        ->toBe([1773.44, 1568.36, 1540.62])
        ->and($spare->rateHistory->first()->effective_from->format('Y-m-d'))->toBe('2025-07-07')
        ->and($spare->rateHistory->first()->source)->toBe(SpareRateHistory::SOURCE_LEGACY_IMPORT);
});

it('keeps both parts when one part number is reused for a different part', function () {
    importSpares([
        spareRow(['PartName' => 'ADBLUE', 'PartNo' => 'F002H50028', 'Rate' => '0', 'TotalAmount' => '0']),
        spareRow(['PartName' => 'BATTERY DIN-60 S5', 'PartNo' => 'F002H50028', 'Rate' => '7070.34', 'TotalAmount' => '8343']),
    ]);

    expect(SpareMaster::count())->toBe(2)
        ->and(SpareMaster::where('spare_code', 'F002H50028')->count())->toBe(1);

    // The loser keeps its part number in the remark rather than losing it.
    $displaced = SpareMaster::whereNull('spare_code')->sole();
    expect($displaced->remark)->toContain('LEGACY PART NO: F002H50028');
});

it('treats rows with no part number as separate spares unless every attribute matches', function () {
    importSpares([
        spareRow(['PartName' => 'AC GRILL', 'PartNo' => '', 'Rate' => '7203.39', 'WEF' => '17/07/19']),
        spareRow(['PartName' => 'AC GRILL', 'PartNo' => '', 'Rate' => '14830.51', 'WEF' => '26/11/18']),
        spareRow(['PartName' => 'AC GRILL', 'PartNo' => '', 'Company' => 'BOSCH', 'Rate' => '900']),
    ]);

    expect(SpareMaster::count())->toBe(2);

    $unknown = SpareMaster::whereHas('brand', fn ($q) => $q->where('name', 'TOYOTA'))->sole();
    expect($unknown->rateHistory)->toHaveCount(2)
        ->and((float) $unknown->rate_before_tax)->toBe(7203.39);
});

it('maps the legacy classification columns onto the spares master', function () {
    importSpares([
        spareRow(['PartName' => 'TYRE 205/55 R16', 'PartNo' => 'T-1', 'DepartmentName' => 'TYRES', 'MainDepartmentName' => 'Tyre', 'HSNACSNo' => '40111010']),
        spareRow(['PartName' => 'ENGINE OIL 5W30', 'PartNo' => 'L-1', 'DepartmentName' => 'LUBRICANTS', 'MainDepartmentName' => 'Value Addition', 'Vat' => '2.5']),
    ]);

    $tyre = SpareMaster::where('spare_code', 'T-1')->sole();
    expect($tyre->inventory_type)->toBe('tyres')
        ->and($tyre->spare_type)->toBe(SpareMaster::TYPE_TYRE)
        ->and($tyre->hsn->code)->toBe('40111010')
        ->and($tyre->tax->name)->toBe('GST 18%')
        ->and($tyre->uom->name)->toBe('PIECES')
        ->and($tyre->inventoryGroup->name)->toBe('SUSPENSION')
        ->and($tyre->inventorySubGroup->name)->toBe('LOWER ARM');

    // "Value Addition" is this workshop's DETAILING; Vat is the half-rate.
    $oil = SpareMaster::where('spare_code', 'L-1')->sole();
    expect($oil->inventory_type)->toBe('lubricants')
        ->and($oil->spare_type)->toBe(SpareMaster::TYPE_COMMON)
        ->and($oil->workshopDepartment->name)->toBe('DETAILING')
        ->and($oil->tax->name)->toBe('GST 5%');
});

it('creates the brands, HSN codes and inventory groups the legacy data references', function () {
    importSpares([
        spareRow(['PartNo' => 'N-1', 'Company' => 'ALBRO GERMANY', 'HSNACSNo' => '87085000', 'InvGroupName' => 'NEW GROUP', 'InvSubGroupName' => 'NEW SUB']),
    ]);

    expect(SpareBrandMaster::where('name', 'ALBRO GERMANY')->exists())->toBeTrue()
        ->and(HsnMaster::where('code', '87085000')->value('gst_percent'))->toEqual(18);

    $parent = InventoryGroupMaster::where('name', 'NEW GROUP')->sole();
    expect($parent->parent_id)->toBeNull()
        ->and(InventoryGroupMaster::where('name', 'NEW SUB')->value('parent_id'))->toBe($parent->id);
});

it('drops legacy junk instead of importing it', function () {
    importSpares([
        // 9-digit HSN and an over-long part no are typos in the source; the
        // "undefined"/NULL sentinels are the old UI's empty values.
        spareRow(['PartNo' => str_repeat('X', 70), 'HSNACSNo' => '870883000', 'Location' => 'undefined', 'Remarks' => 'NULL', 'PartDescription' => 'undefined', 'WEF' => '']),
    ]);

    $spare = SpareMaster::sole();
    expect($spare->spare_code)->toBeNull()
        ->and($spare->hsn_id)->toBeNull()
        ->and($spare->description)->toBeNull()
        ->and($spare->remark)->toBeNull()
        ->and($spare->rateHistory->first()->effective_from)->toBeNull()
        ->and(HsnMaster::where('code', '870883000')->exists())->toBeFalse();
});

it('imports via the import:spares command and fails loudly on a bad path', function () {
    $dir = storage_path('framework/testing/master-data-'.uniqid());
    File::ensureDirectoryExists($dir);
    File::put($dir.'/Spare.csv', SPARE_HEADER."\n".spareRow()."\n");

    $this->artisan('import:spares', ['--path' => $dir])->assertSuccessful();
    expect(SpareMaster::where('spare_code', '48068-0D081')->exists())->toBeTrue();

    $this->artisan('import:spares', ['--path' => $dir.'/nope'])->assertFailed();

    File::deleteDirectory($dir);
});

it('is idempotent — re-running imports nothing twice', function () {
    $rows = [
        spareRow(['Rate' => '1540.62', 'WEF' => '01/02/21']),
        spareRow(['Rate' => '1773.44', 'WEF' => '07/07/25']),
        spareRow(['PartName' => 'AC GRILL', 'PartNo' => '']),
    ];

    importSpares($rows);
    importSpares($rows);

    expect(SpareMaster::count())->toBe(2)
        ->and(SpareRateHistory::count())->toBe(3);
});
