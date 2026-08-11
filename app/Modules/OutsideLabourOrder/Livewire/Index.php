<?php

namespace App\Modules\OutsideLabourOrder\Livewire;

use App\Modules\BayMaster\Models\BayMaster;
use App\Modules\EmployeeMaster\Models\EmployeeMaster;
use App\Modules\OutsideLabourOrder\Models\OutsideLabourOrder;
use App\Modules\PriorityMaster\Models\PriorityMaster;
use Flux\Flux;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use Symfony\Component\HttpFoundation\StreamedResponse;

#[Layout('layouts.app')]
#[Title('Outside Labour Orders')]
class Index extends Component
{
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url(as: 'status')]
    public string $statusFilter = 'all';

    #[Url(as: 'priority')]
    public string $priorityFilter = 'all';

    #[Url(as: 'tech')]
    public string $technicianFilter = 'all';

    #[Url(as: 'bay')]
    public string $bayFilter = 'all';

    #[Url(as: 'sort')]
    public string $sortBy = 'created_at';

    #[Url(as: 'dir')]
    public string $sortDirection = 'desc';

    protected array $sortable = ['id', 'order_no', 'status', 'priority_id', 'created_at', 'started_at', 'ended_at'];

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatingPriorityFilter(): void
    {
        $this->resetPage();
    }

    public function updatingTechnicianFilter(): void
    {
        $this->resetPage();
    }

    public function updatingBayFilter(): void
    {
        $this->resetPage();
    }

    public function sort(string $column): void
    {
        if (! in_array($column, $this->sortable, true)) {
            return;
        }

        if ($this->sortBy === $column) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $column;
            $this->sortDirection = 'asc';
        }
    }

    public function delete(int $id): void
    {
        $this->authorize('outside_labour_order.delete');

        OutsideLabourOrder::findOrFail($id)->delete();

        Flux::toast(text: 'Work Order #'.$id.' deleted.', variant: 'success');
    }

    public function clearFilters(): void
    {
        $this->reset(['search', 'statusFilter', 'priorityFilter', 'technicianFilter', 'bayFilter']);
        $this->resetPage();
    }

    #[Computed]
    public function technicians()
    {
        return EmployeeMaster::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']);
    }

    #[Computed]
    public function bays()
    {
        return BayMaster::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']);
    }

    /**
     * The four counters the CSV asks for.
     *
     * @return array{pending:int, active:int, completed:int, cancelled:int}
     */
    protected function kpis(): array
    {
        $counts = OutsideLabourOrder::query()
            ->selectRaw('status, count(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        return [
            'pending' => (int) ($counts[OutsideLabourOrder::STATUS_ASSIGNMENT_PENDING] ?? 0),
            // "Active" spans everything a technician currently holds.
            'active' => (int) ($counts[OutsideLabourOrder::STATUS_ASSIGNED] ?? 0)
                + (int) ($counts[OutsideLabourOrder::STATUS_WIP] ?? 0)
                + (int) ($counts[OutsideLabourOrder::STATUS_ON_HOLD] ?? 0),
            'completed' => (int) ($counts[OutsideLabourOrder::STATUS_COMPLETED] ?? 0),
            'cancelled' => (int) ($counts[OutsideLabourOrder::STATUS_CANCELLED] ?? 0),
        ];
    }

    /** Stream the Outside Labour Status Report CSV — per-order time metrics. */
    public function download(): StreamedResponse
    {
        $this->authorize('outside_labour_order.view');

        $rows = OutsideLabourOrder::query()
            ->with(['jobCard:id,job_card_no', 'technician:id,name', 'items:id,outside_labour_order_id,hours', 'pauses'])
            ->when(trim($this->search) !== '', fn ($q) => $q->whereLike('order_no', '%'.trim($this->search).'%', caseSensitive: false))
            ->when($this->statusFilter !== 'all', fn ($q) => $q->where('status', $this->statusFilter))
            ->when($this->technicianFilter !== 'all', fn ($q) => $q->where('technician_id', (int) $this->technicianFilter))
            ->orderBy($this->sortBy, $this->sortDirection)
            ->get();

        $filename = 'work-order-analysis-'.Carbon::now()->format('Ymd-His').'.csv';
        $statuses = OutsideLabourOrder::statuses();

        return response()->streamDownload(function () use ($rows, $statuses) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['OLO No', 'Job Card', 'Technician', 'Status', 'Gross Mins', 'Pause Mins', 'Net TAT Mins', 'Item Hours', 'Started', 'Ended']);

            foreach ($rows as $r) {
                fputcsv($out, [
                    $r->order_no,
                    $r->jobCard?->job_card_no,
                    $r->technician?->name,
                    $statuses[$r->status] ?? $r->status,
                    $r->grossMinutes() ?? '',
                    $r->pauseMinutes(),
                    $r->netTatMinutes() ?? '',
                    $r->totalItemHours(),
                    $r->started_at?->format('Y-m-d H:i'),
                    $r->ended_at?->format('Y-m-d H:i'),
                ]);
            }

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    public function render()
    {
        $search = trim($this->search);

        $rows = OutsideLabourOrder::query()
            ->with([
                'jobCard:id,job_card_no,customer_id,customer_vehicle_id',
                'jobCard.customer:id,first_name,last_name',
                'jobCard.customerVehicle:id,registration_no',
                'technician:id,name',
                'priority:id,name',
                'bay:id,name',
                'template:id,name',
            ])
            ->withCount('items')
            ->when($search !== '', fn ($q) => $q->search($search))
            ->when($this->statusFilter !== 'all', fn ($q) => $q->where('status', $this->statusFilter))
            ->when($this->priorityFilter !== 'all', fn ($q) => $q->where('priority_id', (int) $this->priorityFilter))
            ->when($this->technicianFilter !== 'all', fn ($q) => $q->where('technician_id', (int) $this->technicianFilter))
            ->when($this->bayFilter !== 'all', fn ($q) => $q->where('bay_id', (int) $this->bayFilter))
            ->orderBy($this->sortBy, $this->sortDirection)
            ->paginate(20);

        return view('outside-labour-order::index', [
            'rows' => $rows,
            'statuses' => OutsideLabourOrder::statuses(),
            'priorities' => PriorityMaster::forScope(PriorityMaster::APPLIES_WORKSHOP)->pluck('name', 'id'),
            'kpis' => $this->kpis(),
        ]);
    }
}
