<?php

namespace App\Modules\VendorMaster\Livewire;

use App\Modules\VendorMaster\Models\VendorMaster;
use App\Modules\VendorTypeMaster\Models\VendorTypeMaster;
use Flux\Flux;
use Illuminate\Database\QueryException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('Vendors')]
class Index extends Component
{
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url(as: 'type')]
    public string $typeFilter = 'all';

    #[Url(as: 'status')]
    public string $statusFilter = 'all';

    #[Url(as: 'sort')]
    public string $sortBy = 'name';

    #[Url(as: 'dir')]
    public string $sortDirection = 'asc';

    protected array $sortable = ['id', 'vendor_code', 'name', 'credit_limit', 'is_active', 'created_at'];

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingTypeFilter(): void
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

    public function delete(int $id): void
    {
        try {
            VendorMaster::findOrFail($id)->delete();
            Flux::toast(text: 'Vendor #'.$id.' deleted.', variant: 'success');
        } catch (QueryException) {
            Flux::toast(text: 'Cannot delete this vendor — they have related records.', variant: 'danger');
        }
    }

    public function render()
    {
        $rows = VendorMaster::query()
            ->with(['vendorTypes:id,name'])
            ->when($this->search !== '', fn ($q) => $q->search($this->search))
            ->when($this->typeFilter !== 'all', fn ($q) => $q->whereHas('vendorTypes', fn ($vt) => $vt->where('vendor_types.id', $this->typeFilter)))
            ->when($this->statusFilter === 'active', fn ($q) => $q->where('is_active', true))
            ->when($this->statusFilter === 'inactive', fn ($q) => $q->where('is_active', false))
            ->orderBy($this->sortBy, $this->sortDirection)
            ->paginate(20);

        return view('vendor-master::index', [
            'rows' => $rows,
            'vendorTypes' => VendorTypeMaster::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']),
        ]);
    }
}
