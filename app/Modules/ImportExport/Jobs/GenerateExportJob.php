<?php

namespace App\Modules\ImportExport\Jobs;

use App\Modules\ImportExport\Contracts\Exportable;
use App\Modules\ImportExport\Events\ExportCompleted;
use App\Modules\ImportExport\Events\ExportProgressUpdated;
use App\Modules\ImportExport\Models\Export;
use App\Support\ModuleRegistry;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use League\Csv\Writer;
use Throwable;

class GenerateExportJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /** Stream rows in chunks of this size — keeps memory flat regardless of total rows. */
    protected const int CHUNK_SIZE = 500;

    /** Broadcast progress every N rows OR every 250ms, whichever first. */
    protected const int BROADCAST_EVERY_ROWS = 250;

    protected const float BROADCAST_EVERY_SECONDS = 0.25;

    public int $timeout = 1800; // 30 min

    public int $tries = 1;

    public function __construct(public Export $export) {}

    public function handle(ModuleRegistry $registry): void
    {
        $this->export->update([
            'status' => 'processing',
            'started_at' => now(),
        ]);

        try {
            $exporter = $this->resolveExporter($registry);

            $total = $exporter->query()->count();
            $this->export->update(['total_rows' => $total]);

            $disk = Storage::disk('local');
            $relativePath = 'exports/'.$this->export->id.'-'.now()->format('Ymd-His').'.csv';
            $absolutePath = $disk->path($relativePath);

            // Ensure directory exists
            @mkdir(dirname($absolutePath), 0755, true);

            $writer = Writer::createFromPath($absolutePath, 'w+');
            $writer->insertOne($exporter->headers());

            $processed = 0;
            $lastBroadcast = microtime(true);

            // Initial 0% broadcast so the modal sees "processing" right away
            $this->broadcastProgress($processed, $total);

            $exporter->query()->chunk(self::CHUNK_SIZE, function ($models) use ($exporter, $writer, &$processed, &$lastBroadcast, $total) {
                foreach ($models as $model) {
                    $writer->insertOne($exporter->row($model));
                    $processed++;
                }

                $now = microtime(true);
                if ($processed % self::BROADCAST_EVERY_ROWS === 0
                    || ($now - $lastBroadcast) >= self::BROADCAST_EVERY_SECONDS) {
                    $this->export->update(['processed_rows' => $processed]);
                    $this->broadcastProgress($processed, $total);
                    $lastBroadcast = $now;
                }
            });

            $this->export->update([
                'status' => 'completed',
                'processed_rows' => $processed,
                'file_path' => $relativePath,
                'file_name' => $exporter->fileName().'-'.now()->format('Ymd-His').'.csv',
                'completed_at' => now(),
                'expires_at' => now()->addHours(24),
            ]);

            $fresh = $this->export->fresh();

            ExportCompleted::dispatch(
                $fresh->id,
                $fresh->user_id,
                $fresh->file_name,
                $fresh->total_rows,
                route('exports.download', $fresh),
            );
        } catch (Throwable $e) {
            $this->export->update([
                'status' => 'failed',
                'error' => $e->getMessage(),
                'completed_at' => now(),
            ]);

            $this->broadcastProgress(
                $this->export->processed_rows,
                $this->export->total_rows,
                'failed',
            );

            throw $e;
        }
    }

    protected function broadcastProgress(?int $processed, ?int $total, ?string $status = null): void
    {
        ExportProgressUpdated::dispatch(
            $this->export->id,
            $this->export->user_id,
            $status ?? 'processing',
            (int) ($processed ?? 0),
            (int) ($total ?? 0),
        );
    }

    protected function resolveExporter(ModuleRegistry $registry): Exportable
    {
        $manifest = $registry->get($this->export->module);

        if (! $manifest || empty($manifest['exportable'])) {
            throw new \RuntimeException("Module {$this->export->module} has no exporter registered.");
        }

        return app($manifest['exportable']);
    }
}
