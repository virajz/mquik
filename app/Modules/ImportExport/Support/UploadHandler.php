<?php

namespace App\Modules\ImportExport\Support;

use App\Modules\ImportExport\Contracts\Importable;
use App\Modules\ImportExport\Models\Import;
use Illuminate\Support\Facades\Storage;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

/**
 * Pipeline for the upload step: store file → parse preview → create Import → suggest mapping.
 * Pulled out of ImportWizard to keep the component focused on state + transitions.
 *
 * Returned shape:
 *   [
 *     'import'         => Import,
 *     'headers'        => string[],
 *     'preview_rows'   => string[][],
 *     'total_rows'     => int,
 *     'suggested_map'  => array<target_col, ?csv_header>,
 *   ]
 */
class UploadHandler
{
    public static function handle(
        TemporaryUploadedFile $file,
        Importable $importer,
        string $module,
        int $userId,
    ): array {
        $stored = $file->storeAs('imports', uniqid('import-', true).'.csv', 'local');
        $preview = CsvPreview::read(Storage::disk('local')->path($stored));

        $import = Import::create([
            'user_id' => $userId,
            'module' => $module,
            'format' => 'csv',
            'status' => 'mapping',
            'file_path' => $stored,
            'original_name' => $file->getClientOriginalName(),
            'total_rows' => $preview['total_data_rows'],
        ]);

        return [
            'import' => $import,
            'headers' => $preview['headers'],
            'preview_rows' => $preview['rows'],
            'total_rows' => $preview['total_data_rows'],
            'suggested_map' => MappingSuggester::suggest($preview['headers'], $importer->columns()),
        ];
    }
}
