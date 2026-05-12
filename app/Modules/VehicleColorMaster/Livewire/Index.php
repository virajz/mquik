<?php

namespace App\Modules\VehicleColorMaster\Livewire;

use App\Modules\VehicleColorMaster\Models\VehicleColorMaster;
use Flux\Flux;
use Illuminate\Database\QueryException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('Vehicle Colors')]
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

    protected array $sortable = ['id', 'name', 'is_active', 'created_at'];

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
        $this->dispatch('vehicle-color-master:edit', id: null);
        Flux::modal('vehicle-color-master-form')->show();
    }

    public function openEdit(int $id): void
    {
        $this->dispatch('vehicle-color-master:edit', id: $id);
        Flux::modal('vehicle-color-master-form')->show();
    }

    #[On('vehicle-color-master:saved')]
    public function refreshAfterSave(): void {}

    public function delete(int $id): void
    {
        $this->authorize('vehicle_color_master.delete');

        try {
            VehicleColorMaster::findOrFail($id)->delete();
            Flux::toast(text: 'Color #'.$id.' deleted.', variant: 'success');
        } catch (QueryException) {
            Flux::toast(text: 'Cannot delete this color — it is still in use.', variant: 'danger');
        }
    }

    public function render()
    {
        $search = $this->search;
        $status = $this->statusFilter;

        $rows = VehicleColorMaster::query()
            ->when($search !== '', fn ($q) => $q->search($search))
            ->when($status === 'active', fn ($q) => $q->where('is_active', true))
            ->when($status === 'inactive', fn ($q) => $q->where('is_active', false))
            ->orderBy($this->sortBy, $this->sortDirection)
            ->paginate(20);

        return view('vehicle-color-master::index', ['rows' => $rows]);
    }
}
