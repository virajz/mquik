<?php

namespace App\Modules\ImportExport\Support;

use Illuminate\Support\Facades\Storage;
use League\Csv\Writer;

/**
 * Captures invalid CSV rows + their reasons to a temp file during import,
 * then promotes that temp file to the storage disk on save.
 *
 * Lifecycle:
 *   $writer = new ErrorFileWriter($importId, $headers);
 *   $writer->record($csvRow, $errors);   // for each failing row
 *   $relativePath = $writer->save();     // null if zero errors
 */
class ErrorFileWriter
{
    protected ?Writer $writer = null;

    protected string $tmpPath;

    protected int $rowCount = 0;

    /**
     * @param  array<int, string>  $sourceHeaders  CSV header row
     */
    public function __construct(int $importId, array $sourceHeaders)
    {
        $this->tmpPath = sys_get_temp_dir().'/import-errors-'.$importId.'-'.uniqid().'.csv';
        $this->writer = Writer::from($this->tmpPath, 'w+');
        $this->writer->insertOne([...$sourceHeaders, 'Error']);
    }

    /**
     * @param  array<int, string>|array<string, string>  $csvRow
     * @param  array<int, string>  $errors
     */
    public function record(array $csvRow, array $errors): void
    {
        $this->writer?->insertOne([...array_values($csvRow), implode('; ', $errors)]);
        $this->rowCount++;
    }

    /**
     * Promote the temp file to the storage disk. Returns the relative path
     * the route('imports.errors', ...) download serves from. Null if no errors.
     */
    public function save(int $importId): ?string
    {
        if ($this->rowCount === 0 || ! file_exists($this->tmpPath)) {
            @unlink($this->tmpPath);

            return null;
        }

        $relativePath = 'imports/errors-'.$importId.'-'.now()->format('Ymd-His').'.csv';
        Storage::disk('local')->put($relativePath, file_get_contents($this->tmpPath));
        @unlink($this->tmpPath);

        return $relativePath;
    }
}
