<?php

namespace App\Modules\GateInOut\Livewire;

use App\Modules\EmployeeMaster\Models\EmployeeMaster;
use App\Modules\GateInOut\Models\GateInOut;
use App\Modules\JobCard\Models\JobCard;
use Flux\Flux;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('Gate In / Out')]
class Index extends Component
{
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    /** Defaults to vehicles still inside (not delivered/billed out). */
    #[Url(as: 'status')]
    public string $statusFilter = 'pending';

    /** 'all' | 'inside' — vehicles that came in and have not left. */
    #[Url(as: 'presence')]
    public string $presenceFilter = 'all';

    #[Url(as: 'source')]
    public string $sourceFilter = 'all';

    #[Url(as: 'from')]
    public string $dateFrom = '';

    #[Url(as: 'to')]
    public string $dateTo = '';

    #[Url(as: 'outfrom')]
    public string $outDateFrom = '';

    #[Url(as: 'outto')]
    public string $outDateTo = '';

    #[Url(as: 'sort')]
    public string $sortBy = 'entered_at';

    #[Url(as: 'dir')]
    public string $sortDirection = 'desc';

    protected array $sortable = ['id', 'gate_event_no', 'entered_at', 'exited_at', 'status', 'created_at', 'registration_no', 'driver_type', 'outward_type'];

    /** Headings whose order comes from a related name, not an own column. */
    protected function sortSubqueries(): array
    {
        return [
            'delivered_by' => EmployeeMaster::select('name')->whereColumn('employees.id', 'gate_visits.delivered_by_id'),
            'exit_by' => EmployeeMaster::select('name')->whereColumn('employees.id', 'gate_visits.exit_by_id'),
            'job_card' => JobCard::select('job_card_no')->whereColumn('job_cards.id', 'gate_visits.job_card_id'),
        ];
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatingPresenceFilter(): void
    {
        $this->resetPage();
    }

    public function updatingSourceFilter(): void
    {
        $this->resetPage();
    }

    public function sort(string $column): void
    {
        if (! in_array($column, $this->sortable, true) && ! array_key_exists($column, $this->sortSubqueries())) {
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
        $this->authorize('gate_in_out.delete');

        GateInOut::findOrFail($id)->delete();

        Flux::toast(text: 'Gate event #'.$id.' deleted.', variant: 'success');
    }

    public function clearFilters(): void
    {
        $this->reset(['search', 'statusFilter', 'presenceFilter', 'sourceFilter', 'dateFrom', 'dateTo', 'outDateFrom', 'outDateTo']);
        $this->resetPage();
    }

    /**
     * The three counters the CSV asks for on the dashboard.
     *
     * @return array{inward:int, trialRun:int, outward:int, inside:int}
     */
    protected function todayKpis(): array
    {
        $today = now()->toDateString();

        return [
            'inward' => GateInOut::whereDate('entered_at', $today)->count(),
            'outward' => GateInOut::whereDate('exited_at', $today)->count(),
            'trialRun' => GateInOut::whereDate('exited_at', $today)->where('outward_type', 'trial_run')->count(),
            'inside' => GateInOut::stillInside()->count(),
        ];
    }

    public function render()
    {
        $search = trim($this->search);

        $rows = GateInOut::query()
            ->with([
                'customer:id,first_name,last_name,phone',
                'customerVehicle:id,registration_no,model_id',
                'customerVehicle.model:id,name,brand_id',
                'customerVehicle.model.brand:id,name',
                'deliveredBy:id,name',
                'exitBy:id,name',
                'entryGate:id,name',
                'exitGate:id,name',
                'parkingSlot:id,name',
                'jobCard:id,job_card_no',
            ])
            ->when($search !== '', fn ($q) => $q->search($search))
            ->when($this->statusFilter !== 'all', fn ($q) => $q->where('status', $this->statusFilter))
            ->when($this->presenceFilter === 'inside', fn ($q) => $q->stillInside())
            ->when($this->sourceFilter !== 'all', fn ($q) => $q->where('source', $this->sourceFilter))
            ->when($this->dateFrom !== '', fn ($q) => $q->whereDate('entered_at', '>=', $this->dateFrom))
            ->when($this->dateTo !== '', fn ($q) => $q->whereDate('entered_at', '<=', $this->dateTo))
            ->when($this->outDateFrom !== '', fn ($q) => $q->whereDate('exited_at', '>=', $this->outDateFrom))
            ->when($this->outDateTo !== '', fn ($q) => $q->whereDate('exited_at', '<=', $this->outDateTo))
            ->when(
                isset($this->sortSubqueries()[$this->sortBy]),
                fn ($q) => $q->orderBy($this->sortSubqueries()[$this->sortBy], $this->sortDirection),
                fn ($q) => $q->orderBy($this->sortBy, $this->sortDirection),
            )
            ->paginate(30);

        return view('gate-in-out::index', [
            'rows' => $rows,
            'statuses' => GateInOut::statuses(),
            'kpis' => $this->todayKpis(),
        ]);
    }
}
