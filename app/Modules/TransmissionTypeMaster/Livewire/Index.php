<?php

namespace App\Modules\TransmissionTypeMaster\Livewire;

use App\Modules\TransmissionTypeMaster\Models\TransmissionTypeMaster;
use Flux\Flux;
use Illuminate\Database\QueryException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('Transmission Types')]
class Index extends Component
{
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url(as: 'status')]
    public string $statusFilter = 'all';

    #[Url(as: 'sort')]
    public string $sortBy = 'name';

    #[Url(as: 'dir')]
    public string $sortDirection = 'asc';

    /** Whitelist sortable columns — never trust the URL */
    protected array $sortable = ['id', 'name', 'code', 'is_active', 'created_at'];

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
        $this->dispatch('transmission-type-master:edit', id: null);
        Flux::modal('transmission-type-master-form')->show();
    }

    public function openEdit(int $id): void
    {
        $this->dispatch('transmission-type-master:edit', id: $id);
        Flux::modal('transmission-type-master-form')->show();
    }

    #[On('transmission-type-master:saved')]
    public function refreshAfterSave(): void
    {
        // Triggers re-render; pagination cursor preserved.
    }

    public function delete(int $id): void
    {
        $this->authorize('transmission_type_master.delete');

        try {
            TransmissionTypeMaster::findOrFail($id)->delete();
            Flux::toast(text: 'Transmission Type #'.$id.' deleted.', variant: 'success');
        } catch (QueryException) {
            // FK restrict — type is in use by vehicle variants
            Flux::toast(
                text: 'Cannot delete this type — it is still in use by one or more records.',
                variant: 'danger',
            );
        }
    }

    public function render()
    {
        $search = $this->search;
        $status = $this->statusFilter;

        $rows = TransmissionTypeMaster::query()
            ->when($search !== '', fn ($q) => $q->search($search))
            ->when($status === 'active', fn ($q) => $q->where('is_active', true))
            ->when($status === 'inactive', fn ($q) => $q->where('is_active', false))
            ->orderBy($this->sortBy, $this->sortDirection)
            ->paginate(20);

        return view('transmission-type-master::index', ['rows' => $rows]);
    }
}
