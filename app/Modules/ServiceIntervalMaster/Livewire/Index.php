<?php

namespace App\Modules\ServiceIntervalMaster\Livewire;

use App\Modules\ServiceIntervalMaster\Models\ServiceIntervalMaster;
use Flux\Flux;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('Service Intervals')]
class Index extends Component
{
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url(as: 'status')]
    public string $statusFilter = 'all';

    #[Url(as: 'sort')]
    public string $sortBy = 'id';

    #[Url(as: 'dir')]
    public string $sortDirection = 'desc';

    /** Whitelist sortable columns to prevent SQL injection via the URL */
    protected array $sortable = ['id', 'name', 'interval_months', 'interval_km', 'created_at', 'updated_at'];

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
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
        $this->dispatch('service-interval-master:edit', id: null);
        Flux::modal('service-interval-master-form')->show();
    }

    public function openEdit(int $id): void
    {
        $this->dispatch('service-interval-master:edit', id: $id);
        Flux::modal('service-interval-master-form')->show();
    }

    #[On('service-interval-master:saved')]
    public function refreshAfterSave(): void
    {
        // Triggers re-render; pagination cursor preserved.
    }

    public function delete(int $id): void
    {
        ServiceIntervalMaster::findOrFail($id)->delete();
        Flux::toast(text: 'ServiceIntervalMaster #'.$id.' deleted.', variant: 'success');
    }

    public function render()
    {
        $search = $this->search;

        $rows = ServiceIntervalMaster::query()
            ->when($search !== '', fn ($q) => $q->search($search))
            ->when($this->statusFilter !== 'all', fn ($q) => $q->where('is_active', $this->statusFilter === 'active'))
            ->orderBy($this->sortBy, $this->sortDirection)
            ->paginate(20);

        return view('service-interval-master::index', ['rows' => $rows]);
    }
}
