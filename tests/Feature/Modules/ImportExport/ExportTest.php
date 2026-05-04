<?php

use App\Models\User;
use App\Modules\ImportExport\Events\ExportCompleted;
use App\Modules\ImportExport\Events\ExportProgressUpdated;
use App\Modules\ImportExport\Jobs\GenerateExportJob;
use App\Modules\ImportExport\Livewire\ExportButton;
use App\Modules\ImportExport\Models\Export;
use App\Modules\SpareBrandMaster\Models\SpareBrandMaster;
use App\Support\ModuleRegistry;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(User::factory()->create());
});

it('dispatches a queued export job when the user starts an export', function () {
    Bus::fake();

    Livewire::test(ExportButton::class, ['module' => 'SpareBrandMaster'])
        ->call('start')
        ->assertSet('status', 'processing');

    expect(Export::count())->toBe(1);
    Bus::assertDispatched(GenerateExportJob::class);
});

it('refuses to export modules without an exportable manifest entry', function () {
    Bus::fake();

    Livewire::test(ExportButton::class, ['module' => 'NonExistentModule'])
        ->call('start')
        ->assertSet('status', 'idle');

    expect(Export::count())->toBe(0);
    Bus::assertNotDispatched(GenerateExportJob::class);
});

it('processes the export job and writes a CSV to storage', function () {
    Storage::fake('local');
    Event::fake([ExportProgressUpdated::class, ExportCompleted::class]);

    SpareBrandMaster::factory()->count(5)->create();

    $export = Export::create([
        'user_id' => auth()->id(),
        'module' => 'SpareBrandMaster',
        'format' => 'csv',
        'status' => 'pending',
    ]);

    (new GenerateExportJob($export))->handle(app(ModuleRegistry::class));

    $fresh = $export->fresh();
    expect($fresh->status)->toBe('completed')
        ->and($fresh->total_rows)->toBe(5)
        ->and($fresh->processed_rows)->toBe(5)
        ->and($fresh->file_path)->not->toBeNull()
        ->and($fresh->file_name)->toContain('spare-brands');

    Storage::disk('local')->assertExists($fresh->file_path);
    Event::assertDispatched(ExportCompleted::class);
});

it('produces a valid CSV with headers and rows', function () {
    Storage::fake('local');

    SpareBrandMaster::factory()->create(['name' => 'BOSCH', 'code' => 'BSH']);
    SpareBrandMaster::factory()->create(['name' => 'DENSO', 'code' => 'DNS']);

    $export = Export::create([
        'user_id' => auth()->id(),
        'module' => 'SpareBrandMaster',
        'format' => 'csv',
        'status' => 'pending',
    ]);

    (new GenerateExportJob($export))->handle(app(ModuleRegistry::class));

    $csv = Storage::disk('local')->get($export->fresh()->file_path);

    expect($csv)->toContain('ID,Name,Code,Active,Notes,"Created At"')
        ->and($csv)->toContain('BOSCH')
        ->and($csv)->toContain('DENSO')
        ->and($csv)->toContain('BSH')
        ->and($csv)->toContain('DNS');
});

it('marks export as failed and rethrows when the job throws', function () {
    Storage::fake('local');

    $export = Export::create([
        'user_id' => auth()->id(),
        'module' => 'NonExistentModuleXyz',
        'format' => 'csv',
        'status' => 'pending',
    ]);

    expect(fn () => (new GenerateExportJob($export))->handle(app(ModuleRegistry::class)))
        ->toThrow(RuntimeException::class);

    expect($export->fresh()->status)->toBe('failed')
        ->and($export->fresh()->error)->toContain('NonExistentModuleXyz');
});

it('blocks downloads from other users', function () {
    Storage::fake('local');

    $otherUser = User::factory()->create();
    $export = Export::create([
        'user_id' => $otherUser->id,
        'module' => 'SpareBrandMaster',
        'status' => 'completed',
        'file_path' => 'exports/test.csv',
        'file_name' => 'test.csv',
    ]);

    Storage::disk('local')->put('exports/test.csv', 'some,csv,content');

    $this->get(route('exports.download', $export))->assertForbidden();
});

it('returns 410 for expired exports', function () {
    Storage::fake('local');

    $export = Export::create([
        'user_id' => auth()->id(),
        'module' => 'SpareBrandMaster',
        'status' => 'completed',
        'file_path' => 'exports/test.csv',
        'file_name' => 'test.csv',
        'expires_at' => now()->subHour(),
    ]);

    Storage::disk('local')->put('exports/test.csv', 'data');

    $this->get(route('exports.download', $export))->assertStatus(410);
});

it('downloads the file for the owner when ready', function () {
    Storage::fake('local');

    $export = Export::create([
        'user_id' => auth()->id(),
        'module' => 'SpareBrandMaster',
        'status' => 'completed',
        'file_path' => 'exports/owner.csv',
        'file_name' => 'spare-brands-test.csv',
        'expires_at' => now()->addHour(),
    ]);

    Storage::disk('local')->put('exports/owner.csv', "Name,Code\nBOSCH,BSH\n");

    $response = $this->get(route('exports.download', $export));

    $response->assertOk();
    expect($response->headers->get('content-disposition'))->toContain('spare-brands-test.csv');
});
