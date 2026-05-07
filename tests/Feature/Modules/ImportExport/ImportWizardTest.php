<?php

use App\Modules\ImportExport\Livewire\ImportWizard;
use App\Modules\ImportExport\Models\Import;
use App\Modules\ImportExport\Support\MappingSuggester;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(adminUser());
});

function makeCsv(string $content): UploadedFile
{
    return UploadedFile::fake()->createWithContent('test.csv', $content);
}

it('starts at step 1 and only opens for the matching module', function () {
    $component = Livewire::test(ImportWizard::class, ['module' => 'SpareBrandMaster'])
        ->assertSet('step', 1);

    // Wrong module — wizard should NOT advance
    $component->dispatch('start-import', module: 'OtherModule')
        ->assertSet('step', 1);

    // Right module — should reset to step 1 (no-op visible to test, but no error)
    $component->dispatch('start-import', module: 'SpareBrandMaster')
        ->assertSet('step', 1);
});

it('rejects modules without an importable manifest entry', function () {
    Livewire::test(ImportWizard::class, ['module' => 'NonExistentModule'])
        ->dispatch('start-import', module: 'NonExistentModule')
        ->assertSet('step', 1); // never advances
});

it('uploads a CSV and advances to step 2 with parsed headers + suggested mapping', function () {
    Storage::fake('local');

    $csv = makeCsv("Brand Name,Code,Active,Notes\nBOSCH,BSH,YES,German engineering\nDENSO,DNS,YES,\n");

    Livewire::test(ImportWizard::class, ['module' => 'SpareBrandMaster'])
        ->set('file', $csv)
        ->call('processUpload')
        ->assertHasNoErrors()
        ->assertSet('step', 2)
        ->assertSet('csvHeaders', ['Brand Name', 'Code', 'Active', 'Notes'])
        ->assertSet('totalDataRows', 2)
        ->assertSet('mapping.name', 'Brand Name')
        ->assertSet('mapping.code', 'Code')
        ->assertSet('mapping.is_active', 'Active')
        ->assertSet('mapping.notes', 'Notes');

    expect(Import::count())->toBe(1);
    expect(Import::first()->status)->toBe('mapping');
});

it('rejects files larger than 10MB', function () {
    $bigCsv = UploadedFile::fake()->create('big.csv', 11 * 1024, 'text/csv');

    Livewire::test(ImportWizard::class, ['module' => 'SpareBrandMaster'])
        ->set('file', $bigCsv)
        ->call('processUpload')
        ->assertHasErrors(['file']);
});

it('rejects non-csv files', function () {
    $txt = UploadedFile::fake()->create('something.pdf', 100, 'application/pdf');

    Livewire::test(ImportWizard::class, ['module' => 'SpareBrandMaster'])
        ->set('file', $txt)
        ->call('processUpload')
        ->assertHasErrors(['file']);
});

it('blocks confirmMapping when required columns are unmapped', function () {
    Storage::fake('local');

    $csv = makeCsv("Wrong Header\nfoo\n");

    $component = Livewire::test(ImportWizard::class, ['module' => 'SpareBrandMaster'])
        ->set('file', $csv)
        ->call('processUpload')
        ->assertSet('step', 2);

    // Force-clear the required mapping
    $component->set('mapping.name', '')->call('confirmMapping')->assertSet('step', 2);
});

it('advances to step 3 when all required columns are mapped', function () {
    Storage::fake('local');

    $csv = makeCsv("Brand Name,Code\nBOSCH,BSH\n");

    Livewire::test(ImportWizard::class, ['module' => 'SpareBrandMaster'])
        ->set('file', $csv)
        ->call('processUpload')
        ->assertSet('step', 2)
        ->call('confirmMapping')
        ->assertSet('step', 3);

    expect(Import::first()->mapping)->toMatchArray(['name' => 'Brand Name', 'code' => 'Code']);
});

it('saves behavior config and advances to step 4 on confirmBehavior', function () {
    Storage::fake('local');

    $csv = makeCsv("Brand Name\nBOSCH\n");

    Livewire::test(ImportWizard::class, ['module' => 'SpareBrandMaster'])
        ->set('file', $csv)
        ->call('processUpload')
        ->call('confirmMapping')
        ->set('duplicateBehavior', 'create_only')
        ->set('errorBehavior', 'halt')
        ->call('confirmBehavior')
        ->assertSet('step', 4)        // advances to processing/results step
        ->assertSet('isDryRun', true); // first run is always dry-run

    expect(Import::first()->behavior)->toMatchArray([
        'duplicates' => 'create_only',
        'errors' => 'halt',
    ]);
});

it('back() decrements step but never below 1', function () {
    Livewire::test(ImportWizard::class, ['module' => 'SpareBrandMaster'])
        ->set('step', 3)
        ->call('back')
        ->assertSet('step', 2)
        ->call('back')
        ->assertSet('step', 1)
        ->call('back')
        ->assertSet('step', 1);
});

// ============== MappingSuggester unit-style tests ==============

it('suggests exact-match column mappings', function () {
    $headers = ['name', 'code', 'is_active'];
    $columns = [
        'name' => ['label' => 'Name'],
        'code' => ['label' => 'Code'],
        'is_active' => ['label' => 'Active'],
    ];

    expect(MappingSuggester::suggest($headers, $columns))->toBe([
        'name' => 'name',
        'code' => 'code',
        'is_active' => 'is_active',
    ]);
});

it('suggests via label when CSV uses human-friendly headers', function () {
    $headers = ['Brand Name', 'Short Code', 'Active'];
    $columns = [
        'name' => ['label' => 'Brand Name'],
        'code' => ['label' => 'Short Code'],
        'is_active' => ['label' => 'Active'],
    ];

    expect(MappingSuggester::suggest($headers, $columns))->toBe([
        'name' => 'Brand Name',
        'code' => 'Short Code',
        'is_active' => 'Active',
    ]);
});

it('suggests via substring match when names are similar', function () {
    $headers = ['Company GSTIN', 'Email Address'];
    $columns = [
        'gstin' => ['label' => 'GSTIN'],
        'email' => ['label' => 'Email'],
    ];

    expect(MappingSuggester::suggest($headers, $columns))->toBe([
        'gstin' => 'Company GSTIN',
        'email' => 'Email Address',
    ]);
});

it('returns null when no plausible match exists', function () {
    $headers = ['Random Column', 'Another One'];
    $columns = [
        'name' => ['label' => 'Brand Name'],
    ];

    expect(MappingSuggester::suggest($headers, $columns))->toBe([
        'name' => null,
    ]);
});
