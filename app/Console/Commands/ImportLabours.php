<?php

namespace App\Console\Commands;

use App\Modules\HsnMaster\Models\HsnMaster;
use App\Modules\InventoryGroupMaster\Models\InventoryGroupMaster;
use App\Modules\LabourMaster\Models\LabourMaster;
use App\Modules\TaxMaster\Models\TaxMaster;
use App\Modules\VehicleSegmentMaster\Models\VehicleSegmentMaster;
use App\Modules\WorkshopDepartmentMaster\Models\WorkshopDepartmentMaster;
use Illuminate\Console\Command;

/**
 * Import the previous-ERP labour master from LabourMaster.csv.
 *
 * The CSV carries a labour line per vehicle segment, so "A/C OVERHAUL" appears
 * once per segment at a different rate. Each of those is its own labour row —
 * that is how the old system priced work and how estimates reference it.
 *
 * Lookups (segment, department, HSN, inventory group) are resolved by name and
 * created when missing, because the CSV is the authority on what the workshop
 * actually charges for.
 */
class ImportLabours extends Command
{
    protected $signature = 'import:labours
        {--path= : Directory holding LabourMaster.csv (defaults to database/seeders/data/master-data)}
        {--fresh : Delete previously imported labour rows first}
        {--dry-run : Report what would happen without writing anything}';

    protected $description = 'Import the previous-ERP labour master (rates per vehicle segment) from LabourMaster.csv';

    /** @var array<string, int> */
    protected array $segments = [];

    /** @var array<string, int> */
    protected array $departments = [];

    /** @var array<string, int> */
    protected array $hsn = [];

    /** @var array<string, int> */
    protected array $groups = [];

    /** @var array<string, int> */
    protected array $taxes = [];

    public function handle(): int
    {
        $path = $this->option('path') ?: database_path('seeders/data/master-data');
        $file = $path.'/LabourMaster.csv';

        if (! is_file($file)) {
            $this->components->error('No LabourMaster.csv in '.$path);

            return self::FAILURE;
        }

        $rows = $this->readCsv($file);

        if ($rows === []) {
            $this->components->error('No data rows found.');

            return self::FAILURE;
        }

        $dryRun = (bool) $this->option('dry-run');

        if ($this->option('fresh') && ! $dryRun) {
            $deleted = LabourMaster::query()->delete();
            $this->components->warn("Cleared {$deleted} existing labour rows.");
        }

        $this->primeLookups();

        $stats = ['imported' => 0, 'updated' => 0, 'skipped_no_name' => 0, 'created_segments' => 0, 'created_departments' => 0];
        $bar = $this->output->createProgressBar(count($rows));

        foreach ($rows as $row) {
            $name = $this->clean($row['LabourName'] ?? '');

            if ($name === null) {
                $stats['skipped_no_name']++;
                $bar->advance();

                continue;
            }

            $segment = $this->clean($row['LabourDesc'] ?? '');
            $department = $this->clean($row['MainDepartmentName'] ?? '');

            $payload = [
                'name' => $name,
                'description' => $this->clean($row['LabourName1'] ?? ''),
                'vehicle_segment_id' => $segment ? $this->segmentId($segment, $stats, $dryRun) : null,
                'workshop_department_id' => $department ? $this->departmentId($department, $stats, $dryRun) : null,
                'inventory_group_id' => $this->groupId($this->clean($row['InvGroupName'] ?? ''), null),
                'inventory_sub_group_id' => $this->groupId($this->clean($row['InvSubGroupName'] ?? ''), null),
                'hsn_id' => $this->hsnId($this->clean($row['HSNACSNo'] ?? '')),
                'tax_id' => $this->taxId($row),
                'rate_before_tax' => $this->decimal($row['Rate'] ?? null),
                // The old column is a 1/0 flag for work sent outside.
                'is_osl' => (string) ($row['OutSideLabour'] ?? '0') === '1',
                'remark' => $this->clean($row['Remarks'] ?? ''),
                'is_active' => true,
            ];

            if ($dryRun) {
                $stats['imported']++;
                $bar->advance();

                continue;
            }

            // A labour is identified by its name *and* segment — the same job at
            // a different segment is a different rate, not a duplicate.
            $existing = LabourMaster::query()
                ->where('name', $name)
                ->where('vehicle_segment_id', $payload['vehicle_segment_id'])
                ->first();

            if ($existing) {
                $existing->update($payload);
                $stats['updated']++;
            } else {
                LabourMaster::create($payload);
                $stats['imported']++;
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        $this->components->twoColumnDetail('Imported', (string) $stats['imported']);
        $this->components->twoColumnDetail('Updated', (string) $stats['updated']);
        $this->components->twoColumnDetail('Skipped (no name)', (string) $stats['skipped_no_name']);
        $this->components->twoColumnDetail('Segments created', (string) $stats['created_segments']);
        $this->components->twoColumnDetail('Departments created', (string) $stats['created_departments']);

        if ($dryRun) {
            $this->components->warn('Dry run — nothing was written.');
        }

        return self::SUCCESS;
    }

    /**
     * @return list<array<string, string>>
     */
    protected function readCsv(string $file): array
    {
        $rows = [];
        $handle = fopen($file, 'r');
        $header = fgetcsv($handle);

        if ($header !== false) {
            $header = array_map('trim', $header);
            // Excel writes a UTF-8 BOM ahead of the first header cell.
            $header[0] = preg_replace('/^\xEF\xBB\xBF/', '', (string) $header[0]);
            $width = count($header);

            while (($r = fgetcsv($handle)) !== false) {
                $rows[] = array_combine($header, array_slice(array_pad($r, $width, ''), 0, $width));
            }
        }

        fclose($handle);

        return $rows;
    }

    protected function primeLookups(): void
    {
        $this->segments = VehicleSegmentMaster::pluck('id', 'name')
            ->mapWithKeys(fn ($id, $name) => [mb_strtoupper((string) $name) => $id])->all();
        $this->departments = WorkshopDepartmentMaster::pluck('id', 'name')
            ->mapWithKeys(fn ($id, $name) => [mb_strtoupper((string) $name) => $id])->all();
        $this->hsn = HsnMaster::pluck('id', 'code')
            ->mapWithKeys(fn ($id, $code) => [trim((string) $code) => $id])->all();
        $this->groups = InventoryGroupMaster::pluck('id', 'name')
            ->mapWithKeys(fn ($id, $name) => [mb_strtoupper((string) $name) => $id])->all();
        $this->taxes = TaxMaster::pluck('id', 'gst_percent')
            ->mapWithKeys(fn ($id, $pct) => [(string) (float) $pct => $id])->all();
    }

    protected function segmentId(string $name, array &$stats, bool $dryRun): ?int
    {
        $key = mb_strtoupper($name);

        if (! empty($this->segments[$key])) {
            return $this->segments[$key];
        }

        if (isset($this->segments[$key])) {
            return null;   // sentinel from a dry run
        }

        if ($dryRun) {
            // Cache a sentinel so the tally counts distinct values, not rows.
            $this->segments[$key] = 0;
            $stats['created_segments']++;

            return null;
        }

        $id = VehicleSegmentMaster::create(['name' => $key, 'is_active' => true])->id;
        $this->segments[$key] = $id;
        $stats['created_segments']++;

        return $id;
    }

    protected function departmentId(string $name, array &$stats, bool $dryRun): ?int
    {
        $key = mb_strtoupper($name);

        if (! empty($this->departments[$key])) {
            return $this->departments[$key];
        }

        if (isset($this->departments[$key])) {
            return null;   // sentinel from a dry run
        }

        if ($dryRun) {
            $this->departments[$key] = 0;
            $stats['created_departments']++;

            return null;
        }

        // The model mirrors this into the HR department list automatically.
        $id = WorkshopDepartmentMaster::create(['name' => $key, 'is_active' => true])->id;
        $this->departments[$key] = $id;
        $stats['created_departments']++;

        return $id;
    }

    protected function groupId(?string $name, ?int $parentId): ?int
    {
        if ($name === null) {
            return null;
        }

        return $this->groups[mb_strtoupper($name)] ?? null;
    }

    protected function hsnId(?string $code): ?int
    {
        return $code === null ? null : ($this->hsn[$code] ?? null);
    }

    /**
     * Tax from the old Service + VAT split — 9 + 9 is an 18% GST line.
     *
     * @param  array<string, string>  $row
     */
    protected function taxId(array $row): ?int
    {
        $percent = (float) ($row['Service'] ?? 0) + (float) ($row['Vat'] ?? 0);

        return $this->taxes[(string) $percent] ?? null;
    }

    protected function clean(?string $value): ?string
    {
        $value = trim((string) $value);
        $value = preg_replace('/\s+/', ' ', $value);

        return $value === '' ? null : mb_strtoupper($value);
    }

    protected function decimal(?string $value): float
    {
        return round((float) str_replace(',', '', (string) $value), 2);
    }
}
