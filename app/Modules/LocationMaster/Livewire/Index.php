<?php

namespace App\Modules\LocationMaster\Livewire;

use App\Modules\LocationMaster\Models\LocationMaster;
use App\Support\RecordReferences;
use Flux\Flux;
use Illuminate\Database\QueryException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('Locations')]
class Index extends Component
{
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url(as: 'status')]
    public string $statusFilter = 'all';

    #[Url(as: 'ho')]
    public string $headOfficeFilter = 'all';

    #[Url(as: 'sort')]
    public string $sortBy = 'name';

    #[Url(as: 'dir')]
    public string $sortDirection = 'asc';

    /** Whitelist sortable columns â never trust the URL */
    protected array $sortable = ['id', 'name', 'code', 'is_active', 'created_at'];

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatingHeadOfficeFilter(): void
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
        $this->dispatch('location-master:edit', id: null);
        Flux::modal('location-master-form')->show();
    }

    public function openEdit(int $id): void
    {
        $this->dispatch('location-master:edit', id: $id);
        Flux::modal('location-master-form')->show();
    }

    #[On('location-master:saved')]
    public function refreshAfterSave(): void
    {
        // Triggers re-render; pagination cursor preserved.
    }

    public function delete(int $id): void
    {
        $this->authorize('location_master.delete');

        try {
            $record = LocationMaster::findOrFail($id);
            $record->delete();
            Flux::toast(text: 'Location #'.$id.' deleted.', variant: 'success');
        } catch (QueryException) {
            $refs = RecordReferences::summary($record);
            Flux::toast(
                text: $refs
                    ? 'Cannot delete — in use by '.$refs.'. Remove those first.'
                    : 'Cannot delete — it is still in use.',
                variant: 'danger',
            );
        }
    }

    public function render()
    {
        $search = $this->search;
        $status = $this->statusFilter;
        $headOffice = $this->headOfficeFilter;

        $rows = LocationMaster::query()
            ->with(['city:id,name', 'state:id,name'])
            ->when($search !== '', fn ($q) => $q->search($search))
            ->when($status === 'active', fn ($q) => $q->where('is_active', true))
            ->when($status === 'inactive', fn ($q) => $q->where('is_active', false))
            ->when($headOffice === 'head_office', fn ($q) => $q->where('is_head_office', true))
            ->when($headOffice === 'branch', fn ($q) => $q->where('is_head_office', false))
            ->orderBy($this->sortBy, $this->sortDirection)
            ->paginate(20);

        return view('location-master::index', ['rows' => $rows]);
    }
}
