<?php

use App\Models\User;
use App\Modules\ImportExport\Events\ImportCompleted;
use App\Modules\ImportExport\Events\ImportProgressUpdated;
use App\Modules\ImportExport\Jobs\ProcessImportJob;
use App\Modules\ImportExport\Models\Import;
use App\Modules\SpareBrandMaster\Models\SpareBrandMaster;
use App\Support\ModuleRegistry;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->actingAs(User::factory()->create());
});

function makeImport(array $overrides = []): Import
{
    Storage::fake('local');

    $csv = "Brand Name,Code,Active,Notes\nBOSCH,BSH,YES,Bearings\nDENSO,DNS,YES,Spark plugs\nNGK,NGK,NO,Inactive\n";
    Storage::disk('local')->put('imports/test.csv', $csv);

    return Import::create(array_merge([
        'user_id' => auth()->id(),
        'module' => 'SpareBrandMaster',
        'format' => 'csv',
        'status' => 'ready',
        'file_path' => 'imports/test.csv',
        'original_name' => 'test.csv',
        'total_rows' => 3,
        'mapping' => [
            'name' => 'Brand Name',
            'code' => 'Code',
            'is_active' => 'Active',
            'notes' => 'Notes',
        ],
        'behavior' => ['duplicates' => 'upsert', 'errors' => 'skip'],
        'dry_run' => false,
    ], $overrides));
}

it('creates new records during a real import', function () {
    Event::fake([ImportProgressUpdated::class, ImportCompleted::class]);

    $import = makeImport();

    (new ProcessImportJob($import))->handle(app(ModuleRegistry::class));

    expect(SpareBrandMaster::count())->toBe(3)
        ->and(SpareBrandMaster::where('name', 'BOSCH')->exists())->toBeTrue()
        ->and(SpareBrandMaster::where('name', 'DENSO')->exists())->toBeTrue()
        ->and(SpareBrandMaster::where('name', 'NGK')->where('is_active', false)->exists())->toBeTrue();

    $fresh = $import->fresh();
    expect($fresh->status)->toBe('completed')
        ->and($fresh->created_count)->toBe(3)
        ->and($fresh->updated_count)->toBe(0)
        ->and($fresh->skipped_count)->toBe(0)
        ->and($fresh->error_count)->toBe(0);

    Event::assertDispatched(ImportCompleted::class);
});

it('writes nothing in dry-run mode but still counts what WOULD happen', function () {
    $import = makeImport(['dry_run' => true]);

    (new ProcessImportJob($import))->handle(app(ModuleRegistry::class));

    expect(SpareBrandMaster::count())->toBe(0); // nothing persisted

    $fresh = $import->fresh();
    expect($fresh->status)->toBe('completed')
        ->and($fresh->created_count)->toBe(3)  // would have created 3
        ->and($fresh->updated_count)->toBe(0);
});

it('updates existing records when behavior is upsert', function () {
    SpareBrandMaster::create(['name' => 'BOSCH', 'code' => 'OLD-CODE', 'is_active' => true]);

    $import = makeImport();

    (new ProcessImportJob($import))->handle(app(ModuleRegistry::class));

    expect(SpareBrandMaster::where('name', 'BOSCH')->first()->code)->toBe('BSH'); // upserted

    $fresh = $import->fresh();
    expect($fresh->created_count)->toBe(2)  // DENSO + NGK new
        ->and($fresh->updated_count)->toBe(1); // BOSCH updated
});

it('skips existing records when behavior is create_only', function () {
    SpareBrandMaster::create(['name' => 'BOSCH', 'code' => 'OLD-CODE', 'is_active' => true]);

    $import = makeImport(['behavior' => ['duplicates' => 'create_only', 'errors' => 'skip']]);

    (new ProcessImportJob($import))->handle(app(ModuleRegistry::class));

    expect(SpareBrandMaster::where('name', 'BOSCH')->first()->code)->toBe('OLD-CODE'); // unchanged

    $fresh = $import->fresh();
    expect($fresh->created_count)->toBe(2)
        ->and($fresh->skipped_count)->toBe(1);
});

it('halts the import when behavior is error and a duplicate is found', function () {
    SpareBrandMaster::create(['name' => 'BOSCH', 'code' => 'OLD-CODE', 'is_active' => true]);

    $import = makeImport(['behavior' => ['duplicates' => 'error', 'errors' => 'halt']]);

    expect(fn () => (new ProcessImportJob($import))->handle(app(ModuleRegistry::class)))
        ->toThrow(RuntimeException::class);

    expect($import->fresh()->status)->toBe('failed');
});

it('writes invalid rows to an error file when behavior is skip', function () {
    Storage::fake('local');

    // Row 2 has empty required name
    $csv = "Brand Name,Code\nBOSCH,BSH\n,EMPTY-NAME\nNGK,NGK\n";
    Storage::disk('local')->put('imports/bad.csv', $csv);

    $import = Import::create([
        'user_id' => auth()->id(),
        'module' => 'SpareBrandMaster',
        'format' => 'csv',
        'status' => 'ready',
        'file_path' => 'imports/bad.csv',
        'original_name' => 'bad.csv',
        'total_rows' => 3,
        'mapping' => ['name' => 'Brand Name', 'code' => 'Code'],
        'behavior' => ['duplicates' => 'upsert', 'errors' => 'skip'],
        'dry_run' => false,
    ]);

    (new ProcessImportJob($import))->handle(app(ModuleRegistry::class));

    $fresh = $import->fresh();
    expect($fresh->status)->toBe('completed')
        ->and($fresh->created_count)->toBe(2)  // BOSCH + NGK
        ->and($fresh->error_count)->toBe(1);    // empty-name row

    expect($fresh->error_file_path)->not->toBeNull();
    Storage::disk('local')->assertExists($fresh->error_file_path);

    $errorCsv = Storage::disk('local')->get($fresh->error_file_path);
    expect($errorCsv)->toContain('Error')
        ->and($errorCsv)->toContain('EMPTY-NAME')
        ->and($errorCsv)->toContain('name field is required');
});

it('halts on the first invalid row when error behavior is halt', function () {
    Storage::fake('local');

    $csv = "Brand Name,Code\nBOSCH,BSH\n,INVALID\nNGK,NGK\n";
    Storage::disk('local')->put('imports/halt.csv', $csv);

    $import = Import::create([
        'user_id' => auth()->id(),
        'module' => 'SpareBrandMaster',
        'format' => 'csv',
        'status' => 'ready',
        'file_path' => 'imports/halt.csv',
        'original_name' => 'halt.csv',
        'total_rows' => 3,
        'mapping' => ['name' => 'Brand Name', 'code' => 'Code'],
        'behavior' => ['duplicates' => 'upsert', 'errors' => 'halt'],
        'dry_run' => false,
    ]);

    expect(fn () => (new ProcessImportJob($import))->handle(app(ModuleRegistry::class)))
        ->toThrow(RuntimeException::class);

    expect(SpareBrandMaster::count())->toBe(1)  // only BOSCH made it before halt
        ->and($import->fresh()->status)->toBe('failed');
});

it('parses YES/NO as booleans for is_active', function () {
    $import = makeImport();

    (new ProcessImportJob($import))->handle(app(ModuleRegistry::class));

    expect(SpareBrandMaster::where('name', 'NGK')->first()->is_active)->toBeFalse()
        ->and(SpareBrandMaster::where('name', 'BOSCH')->first()->is_active)->toBeTrue();
});

it('blocks downloading another user error file', function () {
    Storage::fake('local');
    Storage::disk('local')->put('imports/errors-1.csv', 'Error\nstuff');

    $other = User::factory()->create();
    $import = Import::create([
        'user_id' => $other->id,
        'module' => 'SpareBrandMaster',
        'format' => 'csv',
        'status' => 'completed',
        'file_path' => 'imports/x.csv',
        'original_name' => 'x.csv',
        'error_file_path' => 'imports/errors-1.csv',
    ]);

    $this->get(route('imports.errors', $import))->assertForbidden();
});

it('downloads the error file for the owner', function () {
    Storage::fake('local');
    Storage::disk('local')->put('imports/errors-2.csv', "Error\nstuff");

    $import = Import::create([
        'user_id' => auth()->id(),
        'module' => 'SpareBrandMaster',
        'format' => 'csv',
        'status' => 'completed',
        'file_path' => 'imports/x.csv',
        'original_name' => 'x.csv',
        'error_file_path' => 'imports/errors-2.csv',
    ]);

    $this->get(route('imports.errors', $import))->assertOk();
});
