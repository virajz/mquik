<?php

namespace App\Modules\ImportExport\Jobs;

use App\Modules\ImportExport\Contracts\Importable;
use App\Modules\ImportExport\Events\ImportCompleted;
use App\Modules\ImportExport\Events\ImportProgressUpdated;
use App\Modules\ImportExport\Models\Import;
use App\Modules\ImportExport\Support\ErrorFileWriter;
use App\Modules\ImportExport\Support\RowProcessor;
use App\Support\ModuleRegistry;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use League\Csv\Reader;
use ReflectionClass;
use RuntimeException;
use Throwable;

class ProcessImportJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /** Broadcast progress every N rows OR every 250ms, whichever first. */
    protected const int BROADCAST_EVERY_ROWS = 100;

    protected const float BROADCAST_EVERY_SECONDS = 0.25;

    public int $timeout = 1800;

    public int $tries = 1;

    public function __construct(public Import $import) {}

    public function handle(ModuleRegistry $registry): void
    {
        $this->import->update([
            'status' => 'processing',
            'started_at' => now(),
            'processed_rows' => 0,
            'created_count' => 0,
            'updated_count' => 0,
            'skipped_count' => 0,
            'error_count' => 0,
            'error_file_path' => null,
            'error' => null,
        ]);

        try {
            $importer = $this->resolveImporter($registry);
            $modelClass = $this->resolveModelClass($importer);
            $reader = $this->openReader();

            $totalRows = max($this->import->total_rows, 1);
            $rowProcessor = new RowProcessor($this->import, $importer, $modelClass);
            $errorWriter = new ErrorFileWriter($this->import->id, $reader->getHeader());

            $this->broadcastProgress($rowProcessor, $totalRows);

            $processed = 0;
            $lastBroadcast = microtime(true);
            $haltOnError = ($this->import->behavior['errors'] ?? 'skip') === 'halt';

            foreach ($reader->getRecords() as $rowIndex => $csvRow) {
                $processed++;
                $errors = $rowProcessor->process($csvRow);

                if (! empty($errors)) {
                    $errorWriter->record($csvRow, $errors);
                    if ($haltOnError) {
                        throw new RuntimeException("Halted on row {$rowIndex} validation: ".implode('; ', $errors));
                    }
                }

                $now = microtime(true);
                if ($processed % self::BROADCAST_EVERY_ROWS === 0
                    || ($now - $lastBroadcast) >= self::BROADCAST_EVERY_SECONDS) {
                    $this->persistCounts($rowProcessor, $processed);
                    $this->broadcastProgress($rowProcessor, $totalRows);
                    $lastBroadcast = $now;
                }
            }

            $this->finalize($rowProcessor, $errorWriter, $processed);
        } catch (Throwable $e) {
            $this->import->update([
                'status' => 'failed',
                'error' => $e->getMessage(),
                'completed_at' => now(),
            ]);

            $this->broadcastProgress(
                $rowProcessor ?? null,
                max($this->import->total_rows, 1),
                'failed',
            );

            throw $e;
        }
    }

    protected function openReader(): Reader
    {
        $reader = Reader::from(Storage::disk('local')->path($this->import->file_path));
        $reader->setHeaderOffset(0);

        return $reader;
    }

    protected function finalize(RowProcessor $rp, ErrorFileWriter $errorWriter, int $processed): void
    {
        $errorFilePath = $errorWriter->save($this->import->id);

        $this->import->update([
            'status' => 'completed',
            'processed_rows' => $processed,
            'created_count' => $rp->created,
            'updated_count' => $rp->updated,
            'skipped_count' => $rp->skipped,
            'error_count' => $rp->errors,
            'error_file_path' => $errorFilePath,
            'completed_at' => now(),
        ]);

        ImportCompleted::dispatch(
            $this->import->id,
            $this->import->user_id,
            (bool) $this->import->dry_run,
            $processed,
            $rp->created,
            $rp->updated,
            $rp->skipped,
            $rp->errors,
            $errorFilePath ? route('imports.errors', $this->import) : null,
        );
    }

    protected function persistCounts(RowProcessor $rp, int $processed): void
    {
        $this->import->update([
            'processed_rows' => $processed,
            'created_count' => $rp->created,
            'updated_count' => $rp->updated,
            'skipped_count' => $rp->skipped,
            'error_count' => $rp->errors,
        ]);
    }

    protected function broadcastProgress(?RowProcessor $rp, int $total, ?string $status = null): void
    {
        ImportProgressUpdated::dispatch(
            $this->import->id,
            $this->import->user_id,
            $status ?? 'processing',
            (bool) $this->import->dry_run,
            (int) $this->import->processed_rows,
            $total,
            $rp->created ?? 0,
            $rp->updated ?? 0,
            $rp->skipped ?? 0,
            $rp->errors ?? 0,
        );
    }

    protected function resolveImporter(ModuleRegistry $registry): Importable
    {
        $manifest = $registry->get($this->import->module);

        if (! $manifest || empty($manifest['importable'])) {
            throw new RuntimeException("Module {$this->import->module} has no importer registered.");
        }

        return app($manifest['importable']);
    }

    /**
     * Convention: App\Modules\Foo\Importers\FooImporter -> App\Modules\Foo\Models\Foo.
     */
    protected function resolveModelClass(Importable $importer): string
    {
        $reflect = new ReflectionClass($importer);
        $namespace = $reflect->getNamespaceName();
        $moduleNamespace = preg_replace('/\\\\Importers$/', '', $namespace) ?? '';
        $moduleName = substr($moduleNamespace, strrpos($moduleNamespace, '\\') + 1);

        $modelClass = $moduleNamespace.'\\Models\\'.$moduleName;

        if (! class_exists($modelClass)) {
            $importerClass = $importer::class;
            throw new RuntimeException("Could not resolve model class for {$importerClass} — expected {$modelClass}");
        }

        return $modelClass;
    }
}
