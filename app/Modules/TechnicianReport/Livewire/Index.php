<?php

namespace App\Modules\TechnicianReport\Livewire;

use App\Modules\EmployeeMaster\Models\EmployeeMaster;
use App\Modules\FinalWorkOrder\Models\FinalWorkOrder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Symfony\Component\HttpFoundation\StreamedResponse;

#[Layout('layouts.app')]
#[Title('Technician Report')]
class Index extends Component
{
    #[Url(as: 'tech')]
    public string $technicianFilter = 'all';

    #[Url(as: 'from')]
    public string $fromDate = '';

    #[Url(as: 'to')]
    public string $toDate = '';

    /** Only count completed work orders in the technician totals. */
    #[Url(as: 'done')]
    public bool $completedOnly = true;

    public function clearFilters(): void
    {
        $this->reset(['technicianFilter', 'fromDate', 'toDate', 'completedOnly']);
    }

    #[Computed]
    public function technicians()
    {
        return EmployeeMaster::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']);
    }

    /**
     * Aggregate work-order time metrics per technician.
     *
     * @return Collection<int, array<string, mixed>>
     */
    protected function rows(): Collection
    {
        $orders = FinalWorkOrder::query()
            ->with(['technician:id,name', 'items:id,final_work_order_id,hours', 'pauses'])
            ->whereNotNull('technician_id')
            ->when($this->technicianFilter !== 'all', fn ($q) => $q->where('technician_id', (int) $this->technicianFilter))
            ->when($this->completedOnly, fn ($q) => $q->where('status', FinalWorkOrder::STATUS_COMPLETED))
            ->when($this->fromDate !== '', fn ($q) => $q->whereDate('created_at', '>=', $this->fromDate))
            ->when($this->toDate !== '', fn ($q) => $q->whereDate('created_at', '<=', $this->toDate))
            ->get();

        return $orders->groupBy('technician_id')->map(function (Collection $group) {
            $net = $group->map->netTatMinutes()->filter(fn ($m) => $m !== null);

            return [
                'technician' => $group->first()->technician?->name ?? '—',
                'orders' => $group->count(),
                'item_hours' => round((float) $group->sum(fn ($o) => $o->totalItemHours()), 2),
                'gross_mins' => (int) $group->sum(fn ($o) => $o->grossMinutes() ?? 0),
                'pause_mins' => (int) $group->sum(fn ($o) => $o->pauseMinutes()),
                'net_tat_mins' => (int) $group->sum(fn ($o) => $o->netTatMinutes() ?? 0),
                'avg_net_tat_mins' => $net->isEmpty() ? 0 : (int) round($net->avg()),
            ];
        })->sortByDesc('net_tat_mins')->values();
    }

    public function download(): StreamedResponse
    {
        $this->authorize('technician_report.export');

        $rows = $this->rows();
        $filename = 'technician-report-'.Carbon::now()->format('Ymd-His').'.csv';

        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Technician', 'Work Orders', 'Item Hours', 'Gross Mins', 'Pause Mins', 'Net TAT Mins', 'Avg Net TAT Mins']);

            foreach ($rows as $r) {
                fputcsv($out, [
                    $r['technician'], $r['orders'], $r['item_hours'],
                    $r['gross_mins'], $r['pause_mins'], $r['net_tat_mins'], $r['avg_net_tat_mins'],
                ]);
            }

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    public function render()
    {
        return view('technician-report::index', ['rows' => $this->rows()]);
    }
}
