<?php

namespace App\Modules\VehicleBrandMaster\Livewire;

use App\Modules\VehicleBrandMaster\Models\VehicleBrandMaster;
use Flux\Flux;
use Illuminate\Database\QueryException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('Vehicle Brands')]
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

    protected array $sortable = ['id', 'name', 'code', 'country', 'is_active', 'created_at'];

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
        $this->dispatch('vehicle-brand-master:edit', id: null);
        Flux::modal('vehicle-brand-master-form')->show();
    }

    public function openEdit(int $id): void
    {
        $this->dispatch('vehicle-brand-master:edit', id: $id);
        Flux::modal('vehicle-brand-master-form')->show();
    }

    #[On('vehicle-brand-master:saved')]
    public function refreshAfterSave(): void {}

    public function delete(int $id): void
    {
        $this->authorize('vehicle_brand_master.delete');

        try {
            VehicleBrandMaster::findOrFail($id)->delete();
            Flux::toast(text: 'Vehicle brand #'.$id.' deleted.', variant: 'success');
        } catch (QueryException) {
            Flux::toast(
                text: 'Cannot delete this brand — it is still in use by one or more models.',
                variant: 'danger',
            );
        }
    }

    public function render()
    {
        $search = $this->search;
        $status = $this->statusFilter;

        $rows = VehicleBrandMaster::query()
            ->when($search !== '', fn ($q) => $q->search($search))
            ->when($status === 'active', fn ($q) => $q->where('is_active', true))
            ->when($status === 'inactive', fn ($q) => $q->where('is_active', false))
            ->orderBy($this->sortBy, $this->sortDirection)
            ->paginate(20);

        return view('vehicle-brand-master::index', ['rows' => $rows]);
    }
}
