<?php

namespace App\Console\Commands\Barcode;

use App\Modules\Barcode\Services\BarcodePdfService;
use Carbon\Carbon;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

#[Signature('barcode:clear-generated-pdfs {--all : Delete every cached PDF, not just expired ones}')]
#[Description('Delete generated barcode PDFs from local storage. By default only files older than the TTL are removed.')]
class ClearGeneratedPdfs extends Command
{
    public function handle(): int
    {
        $disk = Storage::disk('local');
        $directory = 'barcode-pdfs';

        if (! $disk->exists($directory)) {
            $this->info('No barcode-pdfs directory — nothing to clean.');

            return self::SUCCESS;
        }

        $files = $disk->files($directory);

        if (empty($files)) {
            $this->info('No PDFs to clean.');

            return self::SUCCESS;
        }

        $deleteAll = (bool) $this->option('all');
        $ttlHours = BarcodePdfService::TTL_HOURS;
        $deleted = 0;
        $kept = 0;

        foreach ($files as $file) {
            if ($deleteAll) {
                $disk->delete($file);
                $deleted++;

                continue;
            }

            $age = now()->diffInHours(Carbon::createFromTimestamp($disk->lastModified($file)));

            if ($age > $ttlHours) {
                $disk->delete($file);
                $deleted++;
            } else {
                $kept++;
            }
        }

        $this->info(sprintf(
            'Cleared %d PDF%s (kept %d still within %dh TTL).',
            $deleted,
            $deleted === 1 ? '' : 's',
            $kept,
            $ttlHours,
        ));

        return self::SUCCESS;
    }
}
