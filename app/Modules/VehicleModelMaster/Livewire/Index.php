<?php

namespace App\Modules\VehicleModelMaster\Livewire;

use App\Modules\VehicleBrandMaster\Models\VehicleBrandMaster;
use App\Modules\VehicleModelMaster\Models\VehicleModelMaster;
use App\Support\RecordReferences;
use Flux\Flux;
use Illuminate\Database\QueryException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('Vehicle Models')]
class Index extends Component
{
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url(as: 'brand')]
    public string $brandFilter = 'all';

    #[Url(as: 'status')]
    public string $statusFilter = 'all';

    #[Url(as: 'sort')]
    public string $sortBy = 'name';

    #[Url(as: 'dir')]
    public string $sortDirection = 'asc';

    protected array $sortable = ['id', 'name', 'vehicle_segment_id', 'is_active', 'created_at'];

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingBrandFilter(): void
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
        $this->dispatch('vehicle-model-master:edit', id: null);
        Flux::modal('vehicle-model-master-form')->show();
    }

    public function openEdit(int $id): void
    {
        $this->dispatch('vehicle-model-master:edit', id: $id);
        Flux::modal('vehicle-model-master-form')->show();
    }

    #[On('vehicle-model-master:saved')]
    public function refreshAfterSave(): void {}

    public function delete(int $id): void
    {
        $this->authorize('vehicle_model_master.delete');

        try {
            $record = VehicleModelMaster::findOrFail($id);
            $record->delete();
            Flux::toast(text: 'Model #'.$id.' deleted.', variant: 'success');
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

    #[Computed]
    public function brands()
    {
        return VehicleBrandMaster::query()->orderBy('name')->get(['id', 'name']);
    }

    public function render()
    {
        $search = $this->search;

        $rows = VehicleModelMaster::query()
            ->with(['brand:id,name', 'vehicleSegment:id,name'])
            ->when($search !== '', fn ($q) => $q->search($search))
            ->when($this->brandFilter !== 'all', fn ($q) => $q->where('brand_id', $this->brandFilter))
            ->when($this->statusFilter === 'active', fn ($q) => $q->where('is_active', true))
            ->when($this->statusFilter === 'inactive', fn ($q) => $q->where('is_active', false))
            ->orderBy($this->sortBy, $this->sortDirection)
            ->paginate(20);

        return view('vehicle-model-master::index', ['rows' => $rows]);
    }
}
