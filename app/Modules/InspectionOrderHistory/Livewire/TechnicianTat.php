<?php

namespace App\Modules\InspectionOrderHistory\Livewire;

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
 * Technician-wise turnaround for a particular job.
 *
 * The unit is the work scope line, not the order: an order can hold a clutch
 * job and an oil change done by different people, and averaging them together
 * says nothing. Time comes from `duration_seconds` — the technician's own
 * running clock, which excludes the pauses they recorded a reason for, so a
 * job stalled waiting for parts does not count against them.
 */
#[Layout('layouts.app')]
#[Title('Technician TAT')]
class TechnicianTat extends Component
{
    #[Url(as: 'q')]
    public string $search = '';

    #[Url(as: 'dept')]
    public string $departmentFilter = 'all';

    #[Url(as: 'stype')]
    public string $serviceTypeFilter = 'all';

    #[Url(as: 'tech')]
    public string $technicianFilter = 'all';

    #[Url(as: 'from')]
    public string $fromDate = '';

    #[Url(as: 'to')]
    public string $toDate = '';

    /** Every line, or only the ones the technician actually finished. */
    #[Url(as: 'done')]
    public bool $completedOnly = true;

    #[Url(as: 'sort')]
    public string $sortBy = 'job';

    #[Url(as: 'dir')]
    public string $sortDirection = 'asc';

    protected array $sortable = ['job', 'technician', 'jobs_done', 'avg_seconds', 'min_seconds', 'max_seconds', 'total_seconds'];

    public function sort(string $column): void
    {
        if (! in_array($column, $this->sortable, true)) {
            return;
        }

        if ($this->sortBy === $column) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $column;
            // Counts and durations are read biggest-first; names alphabetically.
            $this->sortDirection = in_array($column, ['job', 'technician'], true) ? 'asc' : 'desc';
        }
    }

    public function clearFilters(): void
    {
        $this->reset(['search', 'departmentFilter', 'serviceTypeFilter', 'technicianFilter', 'fromDate', 'toDate', 'completedOnly']);
    }

    /**
     * One row per job × technician.
     *
     * A "job" is whatever names the line — job description, labour, package or
     * requested repair — falling back to the typed description so nothing is
     * silently dropped from the comparison.
     */
    protected function baseQuery(): QueryBuilder
    {
        $jobLabel = "COALESCE(jd.name, lb.name, sp.name, rr.name, s.description, 'Unnamed')";

        $query = DB::table('vehicle_inspection_order_scopes as s')
            ->join('vehicle_inspection_orders as o', 'o.id', '=', 's.vehicle_inspection_order_id')
            // The line's own technician when set, otherwise whoever holds the order.
            ->leftJoin('employees as e', 'e.id', '=', DB::raw('COALESCE(s.technician_id, o.technician_id)'))
            ->leftJoin('job_descriptions as jd', 'jd.id', '=', 's.job_description_id')
            ->leftJoin('labours as lb', 'lb.id', '=', 's.labour_id')
            ->leftJoin('service_packages as sp', 'sp.id', '=', 's.service_package_id')
            ->leftJoin('requested_repairs as rr', 'rr.id', '=', 's.requested_repair_id')
            ->where('s.duration_seconds', '>', 0)
            ->when($this->completedOnly, fn ($q) => $q->where('s.work_status', 'completed'))
            ->when($this->departmentFilter !== 'all', fn ($q) => $q->where('o.department_id', (int) $this->departmentFilter))
            ->when($this->serviceTypeFilter !== 'all', fn ($q) => $q->where('o.service_type_id', (int) $this->serviceTypeFilter))
            ->when($this->technicianFilter !== 'all', fn ($q) => $q->whereRaw('COALESCE(s.technician_id, o.technician_id) = ?', [(int) $this->technicianFilter]))
            ->when($this->fromDate !== '', fn ($q) => $q->whereDate(DB::raw('COALESCE(s.completed_at, o.ended_at, s.updated_at)'), '>=', $this->fromDate))
            ->when($this->toDate !== '', fn ($q) => $q->whereDate(DB::raw('COALESCE(s.completed_at, o.ended_at, s.updated_at)'), '<=', $this->toDate));

        $search = trim($this->search);

        if ($search !== '') {
            $needle = '%'.mb_strtolower($search).'%';
            $query->whereRaw("LOWER({$jobLabel}) LIKE ?", [$needle]);
        }

        return $query
            ->selectRaw("{$jobLabel} as job")
            ->selectRaw("COALESCE(e.name, '— unassigned —') as technician")
            ->selectRaw('COUNT(*) as jobs_done')
            ->selectRaw('SUM(s.duration_seconds) as total_seconds')
            ->selectRaw('AVG(s.duration_seconds) as avg_seconds')
            ->selectRaw('MIN(s.duration_seconds) as min_seconds')
            ->selectRaw('MAX(s.duration_seconds) as max_seconds')
            ->groupByRaw("{$jobLabel}, COALESCE(e.name, '— unassigned —')");
    }

    /**
     * The shop's average per job, so each technician's row can say how far off
     * the pace they are — a raw average alone invites no comparison.
     *
     * @return array<string, float>
     */
    protected function jobBaselines(): array
    {
        $inner = $this->baseQuery();

        return DB::query()
            ->fromSub($inner, 'g')
            ->selectRaw('g.job, SUM(g.total_seconds) / NULLIF(SUM(g.jobs_done), 0) as job_avg')
            ->groupBy('g.job')
            ->pluck('job_avg', 'job')
            ->map(fn ($v) => (float) $v)
            ->all();
    }

    #[Computed]
    public function rows()
    {
        $direction = $this->sortDirection === 'asc' ? 'asc' : 'desc';

        $rows = DB::query()
            ->fromSub($this->baseQuery(), 'g')
            ->orderBy($this->sortBy, $direction)
            // Ties inside one job read best grouped together.
            ->orderBy('job')
            ->orderBy('technician')
            ->limit(500)
            ->get();

        $baselines = $this->jobBaselines();

        return $rows->map(function ($r) use ($baselines) {
            $avg = (float) $r->avg_seconds;
            $baseline = $baselines[$r->job] ?? null;

            $r->avg_seconds = $avg;
            $r->baseline_seconds = $baseline;
            $r->delta_seconds = $baseline !== null ? $avg - $baseline : null;

            return $r;
        });
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
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    #[Computed]
    public function technicians()
    {
        return EmployeeMaster::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']);
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
        $this->authorize('inspection_order_history.export');

        $rows = $this->rows;
        $filename = 'technician-tat-'.Carbon::now()->format('Ymd-His').'.csv';

        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Job', 'Technician', 'Jobs Done', 'Average', 'Fastest', 'Slowest', 'Total', 'Shop Average', 'Difference']);

            foreach ($rows as $r) {
                fputcsv($out, [
                    $r->job,
                    $r->technician,
                    $r->jobs_done,
                    self::humanise($r->avg_seconds),
                    self::humanise((float) $r->min_seconds),
                    self::humanise((float) $r->max_seconds),
                    self::humanise((float) $r->total_seconds),
                    self::humanise($r->baseline_seconds),
                    $r->delta_seconds === null ? '' : ($r->delta_seconds > 0 ? '+' : '-').self::humanise(abs($r->delta_seconds)),
                ]);
            }

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    public function render()
    {
        return view('inspection-order-history::technician-tat');
    }
}
