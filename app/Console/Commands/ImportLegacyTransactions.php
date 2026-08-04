<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Imports the old-ERP transactional history from `public/data CSV/` into the
 * new schema, re-attaching each record to the already-imported customers /
 * vehicles / masters via their legacy keys.
 *
 * Design: streams each CSV row-by-row (never loads a whole file), resolves FKs
 * from lookup maps built once, and bulk-inserts in chunks. Idempotent — rows
 * whose `legacy_id` already exists are skipped, so it is safe to re-run.
 *
 * Job Cards are imported in two passes: pass 1 writes the header rows, pass 2
 * writes the child rows (requested repairs + vehicle-inventory checklist) keyed
 * on the freshly-created ids.
 */
class ImportLegacyTransactions extends Command
{
    protected $signature = 'import:legacy-transactions
        {--only=jobcards : Comma-separated document types to import (jobcards)}
        {--fresh : Delete previously legacy-imported rows for the chosen types first}
        {--limit=0 : Import at most N rows per type (0 = all; for a dry test)}';

    protected $description = 'Import old-ERP transactional history (job cards, …) from public/data CSV/';

    protected string $dir;

    protected string $masterDir;

    /** @var array<string, object{id:int, customer_id:int}> normalised reg-no → vehicle */
    protected array $vehicleByReg = [];

    /** @var array<string, int> old employee legacy_id → new id */
    protected array $advisorByLegacy = [];

    /** @var array<string, int> normalised employee name → id (for the Mech column) */
    protected array $employeeByName = [];

    /** @var array<string, int> UPPER dept name → id */
    protected array $deptByName = [];

    /** @var array<string, int> old ServiceType id → new service_type_id */
    protected array $serviceTypeByOldId = [];

    /** @var array<string, int> old Repairs id → new requested_repair_id */
    protected array $repairByOldId = [];

    /** @var array<string, int> old InvInsideVeh id → new vehicle_inventory_item_id */
    protected array $invItemByOldId = [];

    /** @var array<string, ?int> normalised insurer name → insurance_company_id (lazily created) */
    protected array $insuranceCache = [];

    /** @var array<string, int> old JobCardNo → new job_card id */
    protected array $jobCardByLegacy = [];

    /** @var array<string, int> old invoice BillNo → the job_card it billed */
    protected array $billNoToJobCard = [];

    /** @var array<string, true> tables already --fresh-cleared this run (shared tables clear once) */
    protected array $freshed = [];

    protected int $fallbackAdvisorId = 0;

    protected int $fallbackDeptId = 0;

    public function handle(): int
    {
        $this->dir = public_path('data CSV');
        $this->masterDir = database_path('seeders/data/master-data');
        if (! is_dir($this->dir)) {
            $this->error("Data folder not found: {$this->dir}");

            return self::FAILURE;
        }

        DB::connection()->disableQueryLog();

        $types = collect(explode(',', (string) $this->option('only')))->map(fn ($t) => trim($t))->filter()->all();
        if (in_array('all', $types, true)) {
            $types = ['jobcards', 'estimates', 'proformas', 'invoices'];
        }

        if (in_array('jobcards', $types, true)) {
            $this->importJobCards();
        }
        if (array_intersect(['estimates', 'proformas', 'invoices'], $types)) {
            $this->buildSharedMaps();
        }
        if (in_array('estimates', $types, true)) {
            $this->importEstimates();
        }
        if (in_array('proformas', $types, true)) {
            $this->importProformas();
        }
        if (in_array('invoices', $types, true)) {
            $this->importInvoices();
        }

        return self::SUCCESS;
    }

    /** Maps shared by the estimate / proforma / invoice importers. */
    protected function buildSharedMaps(): void
    {
        if ($this->vehicleByReg) {
            return; // already built by job-card import in the same run
        }
        $this->info('Building shared lookup maps…');
        DB::table('customer_vehicles')->select('id', 'customer_id', 'registration_no')->orderBy('id')
            ->chunk(5000, function ($chunk) {
                foreach ($chunk as $v) {
                    $key = $this->normReg($v->registration_no);
                    if ($key !== '' && ! isset($this->vehicleByReg[$key])) {
                        $this->vehicleByReg[$key] = (object) ['id' => $v->id, 'customer_id' => $v->customer_id];
                    }
                }
            });
        $this->employeeByName = DB::table('employees')->get(['id', 'name'])
            ->mapWithKeys(fn ($e) => [$this->normName($e->name) => $e->id])->all();
        $this->deptByName = DB::table('workshop_departments')->get(['id', 'name'])
            ->mapWithKeys(fn ($d) => [strtoupper($d->name) => $d->id])->all();

        // Job-card links: by legacy JobCardNo (estimates) and by legacy bill no (invoices).
        DB::table('job_cards')->whereNotNull('legacy_id')->select('id', 'legacy_id', 'legacy_bill_no')->orderBy('id')
            ->chunk(5000, function ($chunk) {
                foreach ($chunk as $jc) {
                    $this->jobCardByLegacy[$jc->legacy_id] = $jc->id;
                    if ($jc->legacy_bill_no) {
                        $this->billNoToJobCard[$jc->legacy_bill_no] = $jc->id;
                    }
                }
            });
    }

    // ---- Estimates / Proformas / Invoices ----------------------------------

    protected function importEstimates(): void
    {
        $this->importDoc(
            csv: 'Estimate Data From Date 01_01_2022.csv',
            detailCsv: 'Estimate Detail Data From Date 01_01_2022.csv',
            table: 'sales_estimates',
            itemTable: 'sales_estimate_items',
            itemFk: 'sales_estimate_id',
            noCol: 'EstiNo',
            detailKey: 'EstiNo',
            label: 'Estimates',
            header: fn (array $r, object $veh) => [
                'estimate_no' => $this->clean($r['EstiNo'] ?? null, 40),
                'status' => 'prepared',
                'job_card_id' => $this->jobCardByLegacy[trim((string) ($r['JobCardNo'] ?? ''))] ?? null,
                'advisor_id' => $this->employeeByName[$this->normName($r['Advisor'] ?? '')] ?? null,
                'technician_id' => $this->employeeByName[$this->normName($r['Mech'] ?? '')] ?? null,
                'department_id' => $this->deptByName[strtoupper(trim((string) ($r['Department'] ?? '')))] ?? null,
                'insurance_company_id' => $this->resolveInsurance($r['InsName'] ?? null),
                'parts_total' => $this->dec($r['Total'] ?? null),
                'labour_total' => $this->dec($r['TotalService'] ?? null),
                'tax_total' => $this->dec($r['TotalVat'] ?? null) + $this->dec($r['TotalAVat'] ?? null),
                'grand_total' => $this->dec($r['EstiGTotal'] ?? $r['GTotal'] ?? null),
                'notes' => $this->clean($r['Remarks'] ?? null),
                'prepared_at' => $this->dt($r['Date'] ?? null),
            ],
        );
    }

    protected function importProformas(): void
    {
        $this->importDoc(
            csv: 'Proforma Data From Date 01_01_2022.csv',
            detailCsv: 'Proforma Detail Data From Date 01_01_2022.csv',
            table: 'proformas',
            itemTable: 'proforma_items',
            itemFk: 'proforma_id',
            noCol: 'ProformaNo',
            detailKey: 'ProFormaNo',
            label: 'Proformas',
            header: fn (array $r, object $veh) => [
                'proforma_no' => $this->clean($r['ProformaNo'] ?? null, 40),
                'status' => 'approved',
                'advisor_id' => $this->employeeByName[$this->normName($r['Advisor'] ?? '')] ?? null,
                'technician_id' => $this->employeeByName[$this->normName($r['Mech'] ?? '')] ?? null,
                'insurance_company_id' => $this->resolveInsurance($r['InsName'] ?? null),
                'discount_total' => $this->dec($r['DiscountAmount'] ?? null),
                'parts_total' => $this->dec($r['Total'] ?? null),
                'labour_total' => $this->dec($r['TotalService'] ?? null),
                'tax_total' => $this->dec($r['TotalVat'] ?? null) + $this->dec($r['TotalAVat'] ?? null),
                'grand_total' => $this->dec($r['GTotal'] ?? null),
                'notes' => $this->clean($r['Remarks'] ?? null),
                'prepared_at' => $this->dt($r['Date'] ?? null),
            ],
        );
    }

    protected function importInvoices(): void
    {
        // Customer invoices (regular) + insurance invoices both land in regular_sales_invoices.
        $this->importDoc(
            csv: 'Customer Invoice Data From Date 01_01_2022.csv',
            detailCsv: 'Invoice Detail Data From Date 01_01_2022.csv',
            table: 'regular_sales_invoices',
            itemTable: 'regular_sales_invoice_items',
            itemFk: 'regular_sales_invoice_id',
            noCol: 'BillNo',
            detailKey: 'BillNo',
            label: 'Customer Invoices',
            header: fn (array $r, object $veh) => $this->invoiceHeader($r, 'BillNo', 'regular'),
        );
        // Insurance invoices — header only (the detail CSV keys on the customer BillNo).
        $this->importDoc(
            csv: 'Insurance Invoice Data From Date 01_01_2022.csv',
            detailCsv: null,
            table: 'regular_sales_invoices',
            itemTable: 'regular_sales_invoice_items',
            itemFk: 'regular_sales_invoice_id',
            noCol: 'InsBillNo',
            detailKey: 'InsBillNo',
            label: 'Insurance Invoices',
            header: fn (array $r, object $veh) => $this->invoiceHeader($r, 'InsBillNo', 'insurance'),
        );
    }

    /** @return array<string, mixed> */
    protected function invoiceHeader(array $r, string $noCol, string $type): array
    {
        return [
            'invoice_no' => $this->clean($r[$noCol] ?? null, 40),
            'invoice_type' => $type,
            'status' => 'finalized',
            'job_card_id' => $this->billNoToJobCard[trim((string) ($r[$noCol] ?? ''))] ?? null,
            'advisor_id' => $this->employeeByName[$this->normName($r['Advisor'] ?? '')] ?? null,
            'technician_id' => $this->employeeByName[$this->normName($r['Mech'] ?? '')] ?? null,
            'insurance_company_id' => $this->resolveInsurance($r['InsName'] ?? null),
            'discount_total' => $this->dec($r['DiscountAmount'] ?? null),
            'parts_total' => $this->dec($r['Total'] ?? null),
            'labour_total' => $this->dec($r['TotalService'] ?? null),
            'tax_total' => $this->dec($r['TotalVat'] ?? null) + $this->dec($r['TotalAVat'] ?? null),
            'grand_total' => $this->dec($r['GTotal'] ?? null),
            'notes' => $this->clean($r['Remarks'] ?? null),
            'invoiced_at' => $this->dt($r['Date'] ?? null),
        ];
    }

    /**
     * Generic header + detail importer for the estimate/proforma/invoice docs.
     * Two passes: header rows (attached to customer/vehicle via reg-no), then
     * detail line items keyed on the header's legacy id.
     *
     * @param  callable(array<string,mixed>, object): array<string,mixed>  $header
     */
    protected function importDoc(string $csv, ?string $detailCsv, string $table, string $itemTable, string $itemFk, string $noCol, string $detailKey, string $label, callable $header): void
    {
        $file = $this->dir.'/'.$csv;
        if (! is_file($file)) {
            $this->warn("{$label}: {$csv} missing — skipped.");

            return;
        }
        $this->info("{$label}: importing…");

        if ($this->option('fresh') && ! isset($this->freshed[$table])) {
            $this->freshed[$table] = true;
            $n = DB::table($table)->whereNotNull('legacy_id')->delete(); // items cascade
            $this->line("  · --fresh: removed {$n} previously-imported rows");
        }

        $existing = DB::table($table)->whereNotNull('legacy_id')->pluck('legacy_id')->flip();
        $seen = [];
        $limit = (int) $this->option('limit');
        $batch = [];
        $imported = $skipped = $noVeh = $rows = 0;

        [$h, $head] = $this->openCsv($file);
        while (($row = fgetcsv($h)) !== false) {
            if ($limit > 0 && $rows >= $limit) {
                break;
            }
            $rows++;
            $r = @array_combine($head, $row);
            if ($r === false) {
                continue;
            }
            $legacy = trim((string) ($r[$noCol] ?? ''));
            if ($legacy === '' || isset($existing[$legacy]) || isset($seen[$legacy])) {
                $skipped++;

                continue;
            }
            $seen[$legacy] = true;

            $veh = ($reg = $this->normReg($r['Veh_No'] ?? '')) !== '' ? ($this->vehicleByReg[$reg] ?? null) : null;
            if (! $veh) {
                $noVeh++;

                continue;
            }

            $at = $this->dt($r['Date'] ?? null) ?? '2022-01-01 00:00:00';
            $batch[] = array_merge([
                'legacy_id' => $legacy,
                'customer_id' => $veh->customer_id,
                'customer_vehicle_id' => $veh->id,
                'created_at' => $at,
                'updated_at' => $at,
            ], $header($r, $veh));

            if (count($batch) >= 1000) {
                DB::table($table)->insert($batch);
                $imported += count($batch);
                $batch = [];
                $this->output->write("\r  · headers {$imported}…");
            }
        }
        if ($batch) {
            DB::table($table)->insert($batch);
            $imported += count($batch);
        }
        fclose($h);
        $this->newLine();
        $this->info("  {$label}: {$imported} headers, {$skipped} skipped, {$noVeh} no-vehicle.");

        if ($detailCsv) {
            $this->importDocItems($detailCsv, $table, $itemTable, $itemFk, $detailKey, $label);
        }
    }

    protected function importDocItems(string $detailCsv, string $table, string $itemTable, string $itemFk, string $keyCol, string $label): void
    {
        $file = $this->dir.'/'.$detailCsv;
        if (! is_file($file)) {
            $this->warn("  {$label}: detail {$detailCsv} missing — items skipped.");

            return;
        }
        $idByLegacy = DB::table($table)->whereNotNull('legacy_id')->pluck('id', 'legacy_id')->all();
        $seq = [];
        $batch = [];
        $count = $rows = 0;
        $limit = (int) $this->option('limit');

        [$h, $head] = $this->openCsv($file);
        while (($row = fgetcsv($h)) !== false) {
            if ($limit > 0 && $rows >= $limit * 15) {
                break;
            }
            $rows++;
            $r = @array_combine($head, $row);
            if ($r === false) {
                continue;
            }
            $parentId = $idByLegacy[trim((string) ($r[$keyCol] ?? ''))] ?? null;
            if (! $parentId) {
                continue;
            }
            $labour = trim((string) ($r['LabourName'] ?? '')) !== '' || stripos((string) ($r['Type'] ?? ''), 'labour') !== false || stripos((string) ($r['Type'] ?? ''), 'service') !== false;
            $desc = $this->clean($labour ? ($r['LabourName'] ?? $r['PartName'] ?? null) : ($r['PartName'] ?? null), 255)
                ?? $this->clean($r['PartDescription'] ?? null, 255) ?? 'ITEM';
            $seq[$parentId] = ($seq[$parentId] ?? 0) + 1;

            $batch[] = [
                $itemFk => $parentId,
                'line_type' => $labour ? 'labour' : 'spare',
                'description' => $desc,
                'hsn_code' => $this->clean($r['HSNACSNo'] ?? null, 20),
                'qty' => $this->dec($r['Qty'] ?? null, 1),
                'unit_rate' => $this->dec($r['Rate'] ?? null),
                'tax_percent' => $this->dec($r['Vat'] ?? null),
                'line_total' => $this->dec($r['Amount'] ?? $r['TotalAmount'] ?? $r['Total'] ?? null),
                'sequence_no' => $seq[$parentId],
                'created_at' => now(),
                'updated_at' => now(),
            ];

            if (count($batch) >= 2000) {
                DB::table($itemTable)->insert($batch);
                $count += count($batch);
                $batch = [];
                $this->output->write("\r  · items {$count}…");
            }
        }
        if ($batch) {
            DB::table($itemTable)->insert($batch);
            $count += count($batch);
        }
        fclose($h);
        $this->newLine();
        $this->info("  {$label}: {$count} line items.");
    }

    // ---- Job Cards ---------------------------------------------------------

    protected function importJobCards(): void
    {
        $file = $this->dir.'/Jobcard Data From Date 01_01_2022.csv';
        if (! is_file($file)) {
            $this->warn('Jobcard CSV missing — skipped.');

            return;
        }

        $this->info('Job Cards: building lookup maps…');
        $this->buildJobCardMaps();

        if ($this->option('fresh')) {
            $n = DB::table('job_cards')->whereNotNull('legacy_id')->delete(); // children cascade
            $this->line("  · --fresh: removed {$n} previously-imported job cards (+ their children)");
        }

        // ---- Pass 1: header rows -------------------------------------------
        $existing = DB::table('job_cards')->whereNotNull('legacy_id')->pluck('legacy_id')->flip();
        $seen = [];
        $flagDept = ['Mechanical' => 'SERVICE', 'Accidental' => 'BODYSHOP', 'Tyre' => 'TYRE', 'ValueAddition' => 'DETAILING', 'Accessories' => 'ACCESSORIES'];

        $limit = (int) $this->option('limit');
        $batch = [];
        $imported = $skippedExisting = $skippedNoVehicle = $rows = 0;

        [$handle, $header] = $this->openCsv($file);
        while (($row = fgetcsv($handle)) !== false) {
            if ($limit > 0 && $rows >= $limit) {
                break;
            }
            $rows++;
            $r = @array_combine($header, $row);
            if ($r === false) {
                continue;
            }

            $legacy = trim((string) ($r['JobCardNo'] ?? ''));
            if ($legacy === '' || isset($existing[$legacy]) || isset($seen[$legacy])) {
                $skippedExisting += $legacy !== '' && (isset($existing[$legacy]) || isset($seen[$legacy])) ? 1 : 0;

                continue;
            }
            $seen[$legacy] = true;

            $reg = $this->normReg($r['Veh_No'] ?? '');
            $vehicle = $reg !== '' ? ($this->vehicleByReg[$reg] ?? null) : null;
            if (! $vehicle) {
                $skippedNoVehicle++;

                continue;
            }

            $openedAt = $this->dt($r['EntryDate'] ?? null) ?? $this->dt($r['Date'] ?? null) ?? '2022-01-01 00:00:00';
            $cancelled = in_array(trim((string) ($r['IsCancel'] ?? '0')), ['1', 'true', 'True'], true);
            $completedAt = $this->dt($r['DateComplition'] ?? null);

            $deptId = $this->fallbackDeptId;
            foreach ($flagDept as $flag => $name) {
                if ($this->truthy($r[$flag] ?? null)) {
                    $deptId = $this->deptByName[$name] ?? $this->fallbackDeptId;
                    break;
                }
            }

            $notes = collect([$this->clean($r['Remark'] ?? null), $this->clean($r['JobStatusRemark'] ?? null)])
                ->filter()->implode(' | ') ?: null;

            $batch[] = [
                'legacy_id' => $legacy,
                'legacy_bill_no' => $this->clean($r['BillNo'] ?? null),
                'job_card_no' => $legacy,
                'customer_id' => $vehicle->customer_id,
                'customer_vehicle_id' => $vehicle->id,
                'workshop_department_id' => $deptId,
                'service_type_id' => $this->firstMapped($r['ServiceType'] ?? null, $this->serviceTypeByOldId),
                'insurance_company_id' => $this->resolveInsurance($r['InsName'] ?? null),
                'assigned_advisor_id' => $this->advisorByLegacy[trim((string) ($r['AdvisorID'] ?? ''))] ?? $this->fallbackAdvisorId,
                'assigned_technician_id' => $this->employeeByName[$this->normName($r['Mech'] ?? '')] ?? null,
                'opened_at' => $openedAt,
                'closed_at' => $completedAt,
                'expected_completion_at' => $this->expectedAt($openedAt, $r['ExpDelTime'] ?? null),
                'km_at_service' => $this->intOrNull($r['InKm'] ?? null),
                'odometer_out' => $this->intOrNull($r['OutKm'] ?? null),
                'avg_mileage' => $this->smallIntOrNull($r['AvgMileage'] ?? null),
                'brought_by' => $this->broughtBy($r['BroughtBy'] ?? null),
                'fuel_level' => $this->fuelFromReading($r['Reading'] ?? null),
                'additional_work' => $this->clean($r['RepairsOther'] ?? null),
                'status' => $cancelled ? 'cancelled' : $this->status($r['JobStatus'] ?? null, $completedAt),
                'cancelled_at' => $cancelled ? $this->dt($r['JcCancelDate'] ?? null) : null,
                'cancellation_notes' => $cancelled ? $this->clean($r['JcCancelRemark'] ?? null) : null,
                'notes' => $notes,
                'created_at' => $openedAt,
                'updated_at' => $openedAt,
            ];

            if (count($batch) >= 1000) {
                DB::table('job_cards')->insert($batch);
                $imported += count($batch);
                $batch = [];
                $this->output->write("\r  · headers {$imported}…");
            }
        }
        if ($batch) {
            DB::table('job_cards')->insert($batch);
            $imported += count($batch);
        }
        fclose($handle);
        $this->newLine();
        $this->info("Pass 1: imported {$imported} headers, skipped {$skippedExisting} already-present, skipped {$skippedNoVehicle} with no matching vehicle.");

        // ---- Pass 2: children (requested repairs + inventory checklist) ----
        $jcIdByLegacy = DB::table('job_cards')->whereNotNull('legacy_id')->pluck('id', 'legacy_id')->all();
        $repairRows = [];
        $invRows = [];
        $repairCount = $invCount = $rows = 0;

        [$handle, $header] = $this->openCsv($file);
        while (($row = fgetcsv($handle)) !== false) {
            if ($limit > 0 && $rows >= $limit) {
                break;
            }
            $rows++;
            $r = @array_combine($header, $row);
            if ($r === false) {
                continue;
            }
            $jcId = $jcIdByLegacy[trim((string) ($r['JobCardNo'] ?? ''))] ?? null;
            if (! $jcId) {
                continue;
            }

            foreach ($this->ids($r['Repairs'] ?? null) as $oldId) {
                if (isset($this->repairByOldId[$oldId])) {
                    $repairRows[] = ['job_card_id' => $jcId, 'requested_repair_id' => $this->repairByOldId[$oldId], 'created_at' => now(), 'updated_at' => now()];
                }
            }
            foreach ($this->ids($r['InvInsideVeh'] ?? null) as $oldId) {
                if (isset($this->invItemByOldId[$oldId])) {
                    $invRows[] = ['job_card_id' => $jcId, 'vehicle_inventory_item_id' => $this->invItemByOldId[$oldId], 'is_present' => true, 'status' => 'present', 'created_at' => now(), 'updated_at' => now()];
                }
            }

            if (count($repairRows) >= 1000) {
                DB::table('job_card_requested_repair')->insertOrIgnore($repairRows);
                $repairCount += count($repairRows);
                $repairRows = [];
            }
            if (count($invRows) >= 1000) {
                DB::table('job_card_inventory_items')->insertOrIgnore($invRows);
                $invCount += count($invRows);
                $invRows = [];
            }
        }
        if ($repairRows) {
            DB::table('job_card_requested_repair')->insertOrIgnore($repairRows);
            $repairCount += count($repairRows);
        }
        if ($invRows) {
            DB::table('job_card_inventory_items')->insertOrIgnore($invRows);
            $invCount += count($invRows);
        }
        fclose($handle);
        $this->info("Pass 2: {$repairCount} requested-repair links, {$invCount} inventory-checklist rows.");
    }

    protected function buildJobCardMaps(): void
    {
        DB::table('customer_vehicles')->select('id', 'customer_id', 'registration_no')->orderBy('id')
            ->chunk(5000, function ($chunk) {
                foreach ($chunk as $v) {
                    $key = $this->normReg($v->registration_no);
                    if ($key !== '' && ! isset($this->vehicleByReg[$key])) {
                        $this->vehicleByReg[$key] = (object) ['id' => $v->id, 'customer_id' => $v->customer_id];
                    }
                }
            });

        $this->advisorByLegacy = DB::table('employees')->whereNotNull('legacy_id')->pluck('id', 'legacy_id')->all();
        $this->employeeByName = DB::table('employees')->get(['id', 'name'])
            ->mapWithKeys(fn ($e) => [$this->normName($e->name) => $e->id])->all();
        $this->deptByName = DB::table('workshop_departments')->get(['id', 'name'])
            ->mapWithKeys(fn ($d) => [strtoupper($d->name) => $d->id])->all();

        $this->fallbackDeptId = $this->deptByName['SERVICE'] ?? (int) DB::table('workshop_departments')->orderBy('id')->value('id');
        $this->fallbackAdvisorId = (int) (DB::table('employees')->where('is_active', true)->orderBy('id')->value('id')
            ?? DB::table('employees')->orderBy('id')->value('id'));

        $this->serviceTypeByOldId = $this->oldMasterMap('ServiceType.csv', 'ServiceID', 'ServiceName', 'service_types');
        $this->repairByOldId = $this->oldMasterMap('Repairs.csv', 'RepairsID', 'RepairsName', 'requested_repairs');
        $this->invItemByOldId = $this->oldMasterMap('InvInsideVeh.csv', 'InvInsideVehID', 'InvInsideVehName', 'vehicle_inventory_items');
    }

    /**
     * Build [old id → new id] by joining an old master CSV (id, name) to a new
     * master table on normalised name.
     *
     * @return array<string, int>
     */
    protected function oldMasterMap(string $csv, string $idCol, string $nameCol, string $table): array
    {
        $path = $this->masterDir.'/'.$csv;
        if (! is_file($path)) {
            $this->warn("  · master {$csv} missing — {$table} links skipped");

            return [];
        }
        $newByName = DB::table($table)->get(['id', 'name'])
            ->mapWithKeys(fn ($m) => [$this->normName($m->name) => $m->id])->all();

        $map = [];
        [$h, $head] = $this->openCsv($path);
        while (($r = fgetcsv($h)) !== false) {
            $rr = @array_combine($head, $r);
            if ($rr === false) {
                continue;
            }
            $oldId = trim((string) ($rr[$idCol] ?? ''));
            $newId = $newByName[$this->normName($rr[$nameCol] ?? '')] ?? null;
            if ($oldId !== '' && $newId) {
                $map[$oldId] = $newId;
            }
        }
        fclose($h);

        return $map;
    }

    /** firstOrCreate the real insurance company for an old InsName (fake seed data won't match). */
    protected function resolveInsurance(?string $raw): ?int
    {
        $name = trim((string) $raw);
        if ($name === '' || strtoupper($name) === 'NULL') {
            return null;
        }
        $key = $this->normName($name);
        if (array_key_exists($key, $this->insuranceCache)) {
            return $this->insuranceCache[$key];
        }
        $id = DB::table('insurance_companies')->whereRaw('upper(name) = ?', [strtoupper($name)])->value('id');
        if (! $id) {
            $id = DB::table('insurance_companies')->insertGetId([
                'name' => mb_substr(strtoupper($name), 0, 255), 'is_active' => true, 'created_at' => now(), 'updated_at' => now(),
            ]);
        }

        return $this->insuranceCache[$key] = (int) $id;
    }

    // ---- field helpers -----------------------------------------------------

    /** @return list<string> */
    protected function ids(?string $v): array
    {
        return collect(explode(',', (string) $v))->map(fn ($x) => trim($x))->filter(fn ($x) => $x !== '')->unique()->values()->all();
    }

    /** @param  array<string,int>  $map */
    protected function firstMapped(?string $csvIds, array $map): ?int
    {
        foreach ($this->ids($csvIds) as $id) {
            if (isset($map[$id])) {
                return $map[$id];
            }
        }

        return null;
    }

    protected function status(?string $jobStatus, ?string $completedAt): string
    {
        $s = strtolower(trim((string) $jobStatus));
        if ($s === '') {
            return $completedAt !== null ? 'closed' : 'open';
        }

        return match (true) {
            str_contains($s, 'deliver') && str_contains($s, 'pending') => 'completed',
            str_contains($s, 'deliver'), str_contains($s, 'vehicle ready') => 'closed',
            str_contains($s, 'bill pending') => 'completed',
            str_contains($s, 'work in progress'), str_contains($s, 'warranty'), str_contains($s, 'inspection'), str_contains($s, 'check point') => 'in_progress',
            str_contains($s, 'part'), str_contains($s, 'po pending'), str_contains($s, 'ipo'), str_contains($s, 'store') => 'awaiting_parts',
            str_contains($s, 'approval'), str_contains($s, 'estimate') => 'awaiting_approval',
            str_contains($s, 'allotment') => 'open',
            default => $completedAt !== null ? 'closed' : 'in_progress',
        };
    }

    protected function broughtBy(?string $v): ?string
    {
        $v = strtolower(trim((string) $v));

        return match ($v) {
            'owner' => 'owner',
            'driver' => 'driver',
            'pick up', 'pickup' => 'pickup',
            'towing' => 'towing',
            default => null,
        };
    }

    protected function fuelFromReading(?string $v): ?string
    {
        return match (strtolower(trim((string) $v))) {
            'full' => 'full',
            'low' => 'quarter',
            default => null,
        };
    }

    protected function expectedAt(string $openedAt, ?string $time): ?string
    {
        $time = trim((string) $time);
        if (! preg_match('/^\d{1,2}:\d{2}(:\d{2})?$/', $time)) {
            return null;
        }
        try {
            return Carbon::parse(substr($openedAt, 0, 10).' '.$time)->format('Y-m-d H:i:s');
        } catch (\Throwable) {
            return null;
        }
    }

    // ---- primitive helpers -------------------------------------------------

    /** @return array{0: resource, 1: array<int,string>} */
    protected function openCsv(string $file): array
    {
        $h = fopen($file, 'r');
        $head = fgetcsv($h);
        $head[0] = preg_replace('/^\x{FEFF}/u', '', $head[0]);

        return [$h, $head];
    }

    protected function normReg(?string $v): string
    {
        return strtoupper(preg_replace('/[^A-Za-z0-9]/', '', (string) $v));
    }

    protected function normName(?string $v): string
    {
        return strtoupper(preg_replace('/[^A-Za-z0-9]/', '', (string) $v));
    }

    protected function dt(?string $v): ?string
    {
        $v = trim((string) $v);
        if ($v === '' || str_starts_with($v, '0000') || str_starts_with($v, '1900') || strtoupper($v) === 'NULL') {
            return null;
        }
        try {
            return Carbon::parse($v)->format('Y-m-d H:i:s');
        } catch (\Throwable) {
            return null;
        }
    }

    protected function intOrNull(?string $v): ?int
    {
        $v = preg_replace('/[^0-9]/', '', (string) $v);

        return $v === '' ? null : (int) $v;
    }

    /** Parse a money/number field, returning $default (0) for blank / NULL / non-numeric. */
    protected function dec(?string $v, float $default = 0): float
    {
        $v = trim((string) $v);
        if ($v === '' || strtoupper($v) === 'NULL') {
            return $default;
        }
        $v = str_replace(',', '', $v);

        return is_numeric($v) ? (float) $v : $default;
    }

    protected function smallIntOrNull(?string $v): ?int
    {
        $n = $this->intOrNull($v);

        return ($n === null || $n > 32767) ? null : $n; // Postgres smallint max; larger = data error
    }

    protected function truthy(?string $v): bool
    {
        $v = trim((string) $v);

        return $v !== '' && ! in_array($v, ['0', 'false', 'False', 'No', 'NULL'], true);
    }

    protected function clean(?string $v, int $len = 1000): ?string
    {
        $v = trim((string) $v);

        return ($v === '' || strtoupper($v) === 'NULL') ? null : mb_substr(strtoupper($v), 0, $len);
    }
}
