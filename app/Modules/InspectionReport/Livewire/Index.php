<?php

namespace App\Modules\InspectionReport\Livewire;

use App\Modules\DigitalInspection\Models\DigitalInspection;
use App\Modules\EmployeeMaster\Models\EmployeeMaster;
use App\Modules\ServiceTypeMaster\Models\ServiceTypeMaster;
use App\Modules\WorkshopDepartmentMaster\Models\WorkshopDepartmentMaster;
use Illuminate\Contracts\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Two questions the inspection sheets can answer that the listing cannot.
 *
 * **Turnaround** — how long a technician takes on a sheet, by day, so a slow
 * week shows up as a shape rather than as a hunch.
 *
 * **Future Jobs** — every checkpoint marked FA where a repair or replacement was
 * recommended and the customer has not approved it. That is the follow-up list:
 * work the workshop has already justified and not yet been paid for.
 */
#[Layout('layouts.app')]
#[Title('Inspection Reports')]
class Index extends Component
{
    public const TAB_TAT = 'tat';

    public const TAB_FUTURE = 'future';

    #[Url(as: 'tab')]
    public string $tab = self::TAB_TAT;

    #[Url(as: 'from')]
    public string $dateFrom = '';

    #[Url(as: 'to')]
    public string $dateTo = '';

    #[Url(as: 'tech')]
    public string $technicianFilter = 'all';

    #[Url(as: 'dept')]
    public string $departmentFilter = 'all';

    #[Url(as: 'stype')]
    public string $serviceTypeFilter = 'all';

    #[Url(as: 'sort')]
    public string $sortBy = 'day';

    #[Url(as: 'dir')]
    public string $sortDirection = 'desc';

    protected array $tatSortable = ['day', 'technician', 'sheets', 'avg_seconds', 'min_seconds', 'max_seconds', 'total_seconds'];

    public function sort(string $column): void
    {
        if ($this->tab !== self::TAB_TAT || ! in_array($column, $this->tatSortable, true)) {
            return;
        }

        if ($this->sortBy === $column) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $column;
            $this->sortDirection = in_array($column, ['day', 'technician'], true) ? 'asc' : 'desc';
        }
    }

    public function clearFilters(): void
    {
        $this->reset(['dateFrom', 'dateTo', 'technicianFilter', 'departmentFilter', 'serviceTypeFilter']);
    }

    /**
     * Elapsed seconds between the two stamps.
     *
     * `extract(epoch from …)` is Postgres-only and the test suite runs on
     * SQLite, so each driver gets the expression it understands.
     */
    protected function tatSecondsExpression(string $start = 'i.started_at', string $end = 'i.completed_at'): string
    {
        return DB::connection()->getDriverName() === 'pgsql'
            ? "extract(epoch from ({$end} - {$start}))"
            : "(julianday({$end}) - julianday({$start})) * 86400";
    }

    /** Date-only, in whatever the driver calls it. */
    protected function dayExpression(string $column): string
    {
        return DB::connection()->getDriverName() === 'pgsql'
            ? "date({$column})"
            : "date({$column})";
    }

    /** Filters shared by both tabs, so the two views always agree. */
    protected function applyScope(QueryBuilder $query, string $dateColumn): QueryBuilder
    {
        return $query
            ->when($this->technicianFilter !== 'all',
                fn ($q) => $q->where('i.assigned_technician_id', (int) $this->technicianFilter))
            ->when($this->departmentFilter !== 'all',
                fn ($q) => $q->where('jc.workshop_department_id', (int) $this->departmentFilter))
            ->when($this->serviceTypeFilter !== 'all',
                fn ($q) => $q->where('jc.service_type_id', (int) $this->serviceTypeFilter))
            ->when($this->dateFrom !== '', fn ($q) => $q->whereDate($dateColumn, '>=', $this->dateFrom))
            ->when($this->dateTo !== '', fn ($q) => $q->whereDate($dateColumn, '<=', $this->dateTo));
    }

    /** One row per day × technician. */
    #[Computed]
    public function tatRows()
    {
        $day = $this->dayExpression('i.completed_at');
        $tat = $this->tatSecondsExpression();

        $inner = DB::table('digital_inspections as i')
            ->leftJoin('job_cards as jc', 'jc.id', '=', 'i.job_card_id')
            ->leftJoin('employees as e', 'e.id', '=', 'i.assigned_technician_id')
            ->whereNotNull('i.started_at')
            ->whereNotNull('i.completed_at');

        $inner = $this->applyScope($inner, 'i.completed_at')
            ->selectRaw("{$day} as day")
            ->selectRaw("COALESCE(e.name, '— unassigned —') as technician")
            ->selectRaw('COUNT(*) as sheets')
            ->selectRaw("SUM({$tat}) as total_seconds")
            ->selectRaw("AVG({$tat}) as avg_seconds")
            ->selectRaw("MIN({$tat}) as min_seconds")
            ->selectRaw("MAX({$tat}) as max_seconds")
            ->groupByRaw("{$day}, COALESCE(e.name, '— unassigned —')");

        return DB::query()
            ->fromSub($inner, 'g')
            ->orderBy($this->sortBy, $this->sortDirection === 'asc' ? 'asc' : 'desc')
            ->orderBy('day', 'desc')
            ->orderBy('technician')
            ->limit(500)
            ->get();
    }

    /**
     * Checkpoints the customer has not said yes to.
     *
     * Marked FA, with a repair or replacement recommended, on a sheet whose
     * customer approval is anything but "approved" — deferred, declined, or
     * never asked. Ordered newest first: the freshest ones are the ones still
     * worth a phone call.
     */
    #[Computed]
    public function futureRows()
    {
        $query = DB::table('digital_inspection_items as it')
            ->join('digital_inspections as i', 'i.id', '=', 'it.digital_inspection_id')
            ->leftJoin('job_cards as jc', 'jc.id', '=', 'i.job_card_id')
            ->leftJoin('customer_vehicles as v', 'v.id', '=', 'jc.customer_vehicle_id')
            ->leftJoin('vehicle_models as vm', 'vm.id', '=', 'v.model_id')
            ->leftJoin('customers as c', 'c.id', '=', 'jc.customer_id')
            ->leftJoin('employees as e', 'e.id', '=', 'i.assigned_technician_id')
            ->leftJoin('inspection_items as ii', 'ii.id', '=', 'it.inspection_item_id')
            ->where('it.outcome', DigitalInspection::ACTION_FUTURE)
            ->whereIn('it.recommendation', ['repair', 'replace', 'skimming'])
            ->where(fn ($q) => $q->whereNull('i.customer_approval')
                ->orWhere('i.customer_approval', '!=', 'approved'));

        return $this->applyScope($query, 'i.created_at')
            ->selectRaw('it.id, i.inspection_no, i.created_at, i.customer_approval')
            ->selectRaw('jc.job_card_no, v.registration_no, vm.name as model_name')
            ->selectRaw("COALESCE(c.first_name, '') || ' ' || COALESCE(c.last_name, '') as customer")
            ->selectRaw("COALESCE(e.name, '— unassigned —') as technician")
            ->selectRaw('ii.name as item_name, it.recommendation, it.severity')
            ->orderByDesc('i.created_at')
            ->limit(500)
            ->get();
    }

    #[Computed]
    public function technicians()
    {
        return EmployeeMaster::query()
            ->where('is_active', true)
            ->whereHas('designation', fn ($q) => $q->whereRaw('upper(name) like ?', ['%TECHNICIAN%']))
            ->orderBy('name')->get(['id', 'name']);
    }

    #[Computed]
    public function departments()
    {
        return WorkshopDepartmentMaster::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']);
    }

    #[Computed]
    public function serviceTypes()
    {
        return ServiceTypeMaster::query()
            ->where('is_active', true)
            ->when($this->departmentFilter !== 'all', fn ($q) => $q->where('workshop_department_id', (int) $this->departmentFilter))
            ->orderBy('name')->get(['id', 'name']);
    }

    /** Seconds as something a service manager reads at a glance. */
    public static function humanise(?float $seconds): string
    {
        if ($seconds === null || $seconds <= 0) {
            return '—';
        }

        $seconds = (int) round($seconds);
        $hours = intdiv($seconds, 3600);
        $minutes = intdiv($seconds % 3600, 60);

        if ($hours > 0) {
            return $minutes > 0 ? "{$hours}h {$minutes}m" : "{$hours}h";
        }

        return $minutes > 0 ? "{$minutes}m" : "{$seconds}s";
    }

    public function download(): StreamedResponse
    {
        $this->authorize('inspection_report.export');

        $isTat = $this->tab === self::TAB_TAT;
        $rows = $isTat ? $this->tatRows : $this->futureRows;
        $filename = ($isTat ? 'inspection-tat-' : 'future-jobs-').Carbon::now()->format('Ymd-His').'.csv';

        return response()->streamDownload(function () use ($rows, $isTat) {
            $out = fopen('php://output', 'w');

            if ($isTat) {
                fputcsv($out, ['Date', 'Technician', 'Sheets', 'Average', 'Fastest', 'Slowest', 'Total']);

                foreach ($rows as $r) {
                    fputcsv($out, [
                        Carbon::parse($r->day)->format('d/m/Y'),
                        $r->technician,
                        $r->sheets,
                        self::humanise((float) $r->avg_seconds),
                        self::humanise((float) $r->min_seconds),
                        self::humanise((float) $r->max_seconds),
                        self::humanise((float) $r->total_seconds),
                    ]);
                }
            } else {
                fputcsv($out, ['Date', 'Inspection', 'Job Card', 'Vehicle', 'Model', 'Customer', 'Checkpoint', 'Recommendation', 'Severity', 'Technician', 'Customer Approval']);

                foreach ($rows as $r) {
                    fputcsv($out, [
                        Carbon::parse($r->created_at)->format('d/m/Y'),
                        $r->inspection_no,
                        $r->job_card_no,
                        $r->registration_no,
                        $r->model_name,
                        trim((string) $r->customer),
                        $r->item_name,
                        $r->recommendation,
                        $r->severity,
                        $r->technician,
                        $r->customer_approval ?? 'not asked',
                    ]);
                }
            }

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    public function render()
    {
        return view('inspection-report::index');
    }
}
