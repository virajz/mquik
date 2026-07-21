<?php

namespace App\Modules\GateInOut\Livewire;

use App\Modules\GateInOut\Models\GateInOut;
use Flux\Flux;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
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

    #[Url(as: 'status')]
    public string $statusFilter = 'all';

    /** 'all' | 'inside' — vehicles that came in and have not left. */
    #[Url(as: 'presence')]
    public string $presenceFilter = 'all';

    #[Url(as: 'source')]
    public string $sourceFilter = 'all';

    #[Url(as: 'from')]
    public string $dateFrom = '';

    #[Url(as: 'to')]
    public string $dateTo = '';

    #[Url(as: 'sort')]
    public string $sortBy = 'entered_at';

    #[Url(as: 'dir')]
    public string $sortDirection = 'desc';

    protected array $sortable = ['id', 'gate_event_no', 'entered_at', 'exited_at', 'status', 'created_at'];

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

    public function openCreate(): void
    {
        $this->dispatch('gate-in-out:edit', id: null);
        Flux::modal('gate-in-out-form')->show();
    }

    public function openEdit(int $id): void
    {
        $this->dispatch('gate-in-out:edit', id: $id);
        Flux::modal('gate-in-out-form')->show();
    }

    #[On('gate-in-out:saved')]
    public function refreshAfterSave(): void
    {
        // re-render
    }

    public function delete(int $id): void
    {
        $this->authorize('gate_in_out.delete');

        GateInOut::findOrFail($id)->delete();

        Flux::toast(text: 'Gate event #'.$id.' deleted.', variant: 'success');
    }

    public function clearFilters(): void
    {
        $this->reset(['search', 'statusFilter', 'presenceFilter', 'sourceFilter', 'dateFrom', 'dateTo']);
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
                'customerVehicle:id,registration_no',
                'entryGate:id,name',
                'exitGate:id,name',
                'parkingSlot:id,name',
                'jobCard:id,job_card_no',
            ])
            ->when($search !== '', fn ($q) => $q->where(function ($q) use ($search) {
                $q->whereLike('registration_no', '%'.$search.'%', caseSensitive: false)
                    ->orWhereLike('gate_event_no', '%'.$search.'%', caseSensitive: false)
                    ->orWhereHas('customer', fn ($c) => $c->whereLike('first_name', '%'.$search.'%', caseSensitive: false));
            }))
            ->when($this->statusFilter !== 'all', fn ($q) => $q->where('status', $this->statusFilter))
            ->when($this->presenceFilter === 'inside', fn ($q) => $q->stillInside())
            ->when($this->sourceFilter !== 'all', fn ($q) => $q->where('source', $this->sourceFilter))
            ->when($this->dateFrom !== '', fn ($q) => $q->whereDate('entered_at', '>=', $this->dateFrom))
            ->when($this->dateTo !== '', fn ($q) => $q->whereDate('entered_at', '<=', $this->dateTo))
            ->orderBy($this->sortBy, $this->sortDirection)
            ->paginate(30);

        return view('gate-in-out::index', [
            'rows' => $rows,
            'statuses' => GateInOut::statuses(),
            'kpis' => $this->todayKpis(),
        ]);
    }
}
