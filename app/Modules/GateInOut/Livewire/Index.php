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

    #[Url(as: 'direction')]
    public string $directionFilter = 'all';

    #[Url(as: 'source')]
    public string $sourceFilter = 'all';

    #[Url(as: 'from')]
    public string $dateFrom = '';

    #[Url(as: 'to')]
    public string $dateTo = '';

    #[Url(as: 'sort')]
    public string $sortBy = 'gated_at';

    #[Url(as: 'dir')]
    public string $sortDirection = 'desc';

    protected array $sortable = ['id', 'gate_event_no', 'gated_at', 'direction', 'created_at'];

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingDirectionFilter(): void
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
        $this->reset(['search', 'directionFilter', 'sourceFilter', 'dateFrom', 'dateTo']);
        $this->resetPage();
    }

    public function render()
    {
        $search = trim($this->search);

        $rows = GateInOut::query()
            ->with(['customer:id,first_name,last_name,phone', 'customerVehicle:id,registration_no'])
            ->when($search !== '', fn ($q) => $q->where(function ($q) use ($search) {
                $q->whereLike('registration_no', '%'.$search.'%', caseSensitive: false)
                    ->orWhereLike('gate_event_no', '%'.$search.'%', caseSensitive: false)
                    ->orWhereHas('customer', fn ($c) => $c->whereLike('first_name', '%'.$search.'%', caseSensitive: false));
            }))
            ->when($this->directionFilter !== 'all', fn ($q) => $q->where('direction', $this->directionFilter))
            ->when($this->sourceFilter !== 'all', fn ($q) => $q->where('source', $this->sourceFilter))
            ->when($this->dateFrom !== '', fn ($q) => $q->whereDate('gated_at', '>=', $this->dateFrom))
            ->when($this->dateTo !== '', fn ($q) => $q->whereDate('gated_at', '<=', $this->dateTo))
            ->orderBy($this->sortBy, $this->sortDirection)
            ->paginate(30);

        return view('gate-in-out::index', [
            'rows' => $rows,
            'directions' => GateInOut::directions(),
        ]);
    }
}
