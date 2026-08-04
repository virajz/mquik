<?php

namespace App\Console\Commands;

use App\Modules\Inventory\Models\StockEntry;
use App\Modules\Inventory\Services\StockIssuer;
use App\Modules\SpareMaster\Models\SpareMaster;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Seeds on-hand balances from a physical count or a legacy stock export.
 *
 * CSV columns (header row required): part_no, qty, rate, batch_no, expiry_date,
 * location. Only part_no and qty are mandatory. Each row becomes one `opening`
 * entry — a FIFO layer — so everything downstream (cost-of-issue, expiry
 * alerts, batch tracing) works from day one.
 *
 * Idempotent per spare: a spare that already carries an opening entry is
 * skipped, so a partial run can simply be repeated. Use --replace to recount.
 */
class ImportOpeningStock extends Command
{
    protected $signature = 'import:opening-stock
        {file : Path to the CSV}
        {--replace : Drop existing opening entries for the spares in the file first}
        {--dry-run : Report what would happen without writing anything}';

    protected $description = 'Import opening stock balances from a CSV (part_no, qty, rate, batch_no, expiry_date, location)';

    public function handle(): int
    {
        $file = $this->argument('file');
        if (! is_file($file)) {
            $this->components->error('No such file: '.$file);

            return self::FAILURE;
        }

        $rows = $this->readCsv($file);
        if ($rows === []) {
            $this->components->error('No data rows found. Expected a header row with at least part_no and qty.');

            return self::FAILURE;
        }

        $spareIds = SpareMaster::query()
            ->whereNotNull('spare_code')
            ->pluck('id', 'spare_code')
            ->all();

        $alreadyOpened = StockEntry::query()
            ->where('entry_type', StockEntry::TYPE_OPENING)
            ->distinct()
            ->pluck('spare_id')
            ->flip()
            ->all();

        $imported = 0;
        $skipped = ['unknown_part' => 0, 'already_opened' => 0, 'zero_qty' => 0];
        $dryRun = (bool) $this->option('dry-run');

        DB::transaction(function () use ($rows, $spareIds, &$alreadyOpened, &$imported, &$skipped, $dryRun) {
            foreach ($rows as $row) {
                $code = mb_strtoupper(trim((string) ($row['part_no'] ?? '')));
                $spareId = $spareIds[$code] ?? null;

                if (! $spareId) {
                    $skipped['unknown_part']++;

                    continue;
                }

                $qty = (float) ($row['qty'] ?? 0);
                if ($qty <= 0) {
                    $skipped['zero_qty']++;

                    continue;
                }

                if ($this->option('replace')) {
                    if (! $dryRun) {
                        StockEntry::where('spare_id', $spareId)->where('entry_type', StockEntry::TYPE_OPENING)->delete();
                    }
                    unset($alreadyOpened[$spareId]);
                }

                if (isset($alreadyOpened[$spareId])) {
                    $skipped['already_opened']++;

                    continue;
                }

                if (! $dryRun) {
                    StockIssuer::receive($spareId, $qty, (float) ($row['rate'] ?? 0), StockEntry::TYPE_OPENING, null, [
                        'batch_no' => $row['batch_no'] ?? null,
                        'expiry_date' => $this->date($row['expiry_date'] ?? null),
                        'location' => $row['location'] ?? null,
                        'notes' => 'Opening stock import',
                    ]);
                }

                $alreadyOpened[$spareId] = true;
                $imported++;
            }

            if ($dryRun) {
                DB::rollBack();
            }
        });

        $this->components->info(sprintf(
            '%s%d opening balances from %d rows. Skipped: %d unknown part no, %d already opened, %d zero qty.',
            $dryRun ? '[dry run] would import ' : 'Imported ',
            $imported, count($rows), $skipped['unknown_part'], $skipped['already_opened'], $skipped['zero_qty'],
        ));

        if ($skipped['unknown_part'] > 0) {
            $this->components->warn('Rows with an unrecognised part no were skipped — check they match `spares.spare_code` exactly.');
        }

        return self::SUCCESS;
    }

    /**
     * @return list<array<string, string>>
     */
    protected function readCsv(string $file): array
    {
        $handle = fopen($file, 'r');
        $header = fgetcsv($handle, escape: '');
        if ($header === false) {
            fclose($handle);

            return [];
        }

        $header = array_map(
            fn ($h) => str_replace([' ', '-'], '_', mb_strtolower(trim((string) $h))),
            $header,
        );
        // Excel writes a UTF-8 BOM ahead of the first header cell.
        $header[0] = preg_replace('/^\xEF\xBB\xBF/', '', (string) $header[0]);

        $rows = [];
        $width = count($header);
        while (($r = fgetcsv($handle, escape: '')) !== false) {
            $rows[] = array_combine($header, array_slice(array_pad($r, $width, ''), 0, $width));
        }
        fclose($handle);

        return $rows;
    }

    /** Accepts d/m/y, d/m/Y and Y-m-d; anything else is treated as no expiry. */
    protected function date(?string $value): ?string
    {
        $value = trim((string) $value);
        if ($value === '' || mb_strtoupper($value) === 'NULL') {
            return null;
        }

        foreach (['d/m/y', 'd/m/Y', 'Y-m-d'] as $format) {
            $date = \DateTime::createFromFormat($format, $value);
            $errors = \DateTime::getLastErrors();
            if ($date && empty($errors['warning_count']) && empty($errors['error_count'])) {
                return $date->format('Y-m-d');
            }
        }

        return null;
    }
}
