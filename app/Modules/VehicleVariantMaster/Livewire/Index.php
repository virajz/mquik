<?php

namespace App\Modules\VehicleVariantMaster\Livewire;

use App\Modules\VehicleModelMaster\Models\VehicleModelMaster;
use App\Modules\VehicleVariantMaster\Models\VehicleVariantMaster;
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
#[Title('Vehicle Variants')]
class Index extends Component
{
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url(as: 'model')]
    public string $modelFilter = 'all';

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

    public function updatingModelFilter(): void
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
        $this->dispatch('vehicle-variant-master:edit', id: null);
        Flux::modal('vehicle-variant-master-form')->show();
    }

    public function openEdit(int $id): void
    {
        $this->dispatch('vehicle-variant-master:edit', id: $id);
        Flux::modal('vehicle-variant-master-form')->show();
    }

    #[On('vehicle-variant-master:saved')]
    public function refreshAfterSave(): void {}

    public function delete(int $id): void
    {
        $this->authorize('vehicle_variant_master.delete');

        try {
            $record = VehicleVariantMaster::findOrFail($id);
            $record->delete();
            Flux::toast(text: 'Variant #'.$id.' deleted.', variant: 'success');
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
    public function models()
    {
        return VehicleModelMaster::query()->with('brand:id,name')->orderBy('name')->get();
    }

    public function render()
    {
        $rows = VehicleVariantMaster::query()
            ->with(['model:id,name,brand_id', 'model.brand:id,name', 'fuelType:id,name', 'transmissionType:id,name'])
            ->when($this->search !== '', fn ($q) => $q->search($this->search))
            ->when($this->modelFilter !== 'all', fn ($q) => $q->where('model_id', $this->modelFilter))
            ->when($this->statusFilter === 'active', fn ($q) => $q->where('is_active', true))
            ->when($this->statusFilter === 'inactive', fn ($q) => $q->where('is_active', false))
            ->orderBy($this->sortBy, $this->sortDirection)
            ->paginate(20);

        return view('vehicle-variant-master::index', ['rows' => $rows]);
    }
}
