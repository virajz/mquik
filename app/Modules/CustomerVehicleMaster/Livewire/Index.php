<?php

namespace App\Modules\CustomerVehicleMaster\Livewire;

use App\Modules\CustomerVehicleMaster\Models\CustomerVehicleMaster;
use Flux\Flux;
use Illuminate\Database\QueryException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('Customer Vehicles')]
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

    protected array $sortable = ['id', 'registration_no', 'year_of_manufacture', 'is_active', 'created_at'];

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

    public function delete(int $id): void
    {
        try {
            CustomerVehicleMaster::findOrFail($id)->delete();
            Flux::toast(text: 'Vehicle #'.$id.' deleted.', variant: 'success');
        } catch (QueryException) {
            Flux::toast(text: 'Cannot delete this vehicle — it has related job cards or invoices.', variant: 'danger');
        }
    }

    public function render()
    {
        $rows = CustomerVehicleMaster::query()
            ->with(['customer:id,first_name,middle_name,last_name,phone', 'model:id,name,brand_id', 'model.brand:id,name', 'variant:id,name', 'color:id,name,hex_code'])
            ->when($this->search !== '', function ($q) {
                $term = '%'.$this->search.'%';
                $rawTerm = $this->search;
                $q->where(function ($query) use ($term, $rawTerm) {
                    $query->whereLike('registration_no', $term, caseSensitive: false)
                        ->orWhereLike('vin', $term, caseSensitive: false)
                        ->orWhereLike('engine_no', $term, caseSensitive: false)
                        ->orWhereHas('customer', fn ($c) => $c->search($rawTerm));
                });
            })
            ->when($this->statusFilter === 'active', fn ($q) => $q->where('is_active', true))
            ->when($this->statusFilter === 'inactive', fn ($q) => $q->where('is_active', false))
            ->orderBy($this->sortBy, $this->sortDirection)
            ->paginate(20);

        return view('customer-vehicle-master::index', ['rows' => $rows]);
    }
}
