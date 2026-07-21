<?php

namespace App\Console\Commands;

use App\Modules\DocumentCollection\Models\DocumentCollection;
use Illuminate\Console\Command;

/**
 * Row 8: "Document Retention — auto delete after specific days of billed."
 *
 * Retires document collections whose retention window has elapsed. This is a
 * SOFT delete: the record and its checklist lines drop out of every normal
 * query but stay recoverable, because these are customer insurance documents
 * and an irreversible purge is not something a scheduler should do unattended.
 */
class ApplyDocumentRetention extends Command
{
    protected $signature = 'documents:apply-retention
        {--dry-run : List what would be retired without touching anything}';

    protected $description = 'Soft-delete document collections whose retention window has elapsed.';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');

        // Only rows explicitly marked for deletion, with a window, still live.
        $candidates = DocumentCollection::query()
            ->with('jobCard:id,closed_at')
            ->where('retention', DocumentCollection::RETENTION_DELETE)
            ->whereNotNull('retention_days')
            ->whereNull('retired_at')
            ->get();

        $due = $candidates->filter(fn (DocumentCollection $dc) => $dc->isRetentionDue());

        if ($due->isEmpty()) {
            $this->components->info('Nothing is due for retention.');

            return self::SUCCESS;
        }

        foreach ($due as $dc) {
            $this->line(sprintf(
                '  %s — due %s%s',
                $dc->doc_collection_no,
                $dc->retentionDueAt()?->format('d M Y') ?? '—',
                $dryRun ? ' (dry run)' : '',
            ));

            if ($dryRun) {
                continue;
            }

            $dc->forceFill(['retired_at' => now()])->saveQuietly();
            $dc->delete();
        }

        $this->components->info(sprintf(
            '%d document collection(s) %s.',
            $due->count(),
            $dryRun ? 'would be retired' : 'retired',
        ));

        return self::SUCCESS;
    }
}
