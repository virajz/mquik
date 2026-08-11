<?php

namespace App\Modules\PickupDrop\Livewire;

use App\Concerns\ScopesToRecord;
use App\Modules\EmployeeMaster\Models\EmployeeMaster;
use App\Modules\PickupDrop\Models\PickupDrop;
use Flux\Flux;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('Pickup / Drop')]
class Index extends Component
{
    use ScopesToRecord;
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url(as: 'status')]
    public string $statusFilter = 'all';

    #[Url(as: 'direction')]
    public string $directionFilter = 'all';

    #[Url(as: 'driver')]
    public string $driverFilter = 'all';

    #[Url(as: 'from')]
    public string $dateFrom = '';

    #[Url(as: 'to')]
    public string $dateTo = '';

    #[Url(as: 'sort')]
    public string $sortBy = 'scheduled_at';

    #[Url(as: 'dir')]
    public string $sortDirection = 'desc';

    protected array $sortable = ['id', 'pickup_drop_no', 'scheduled_at', 'status', 'created_at'];

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatingDirectionFilter(): void
    {
        $this->resetPage();
    }

    public function updatingDriverFilter(): void
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
        $this->authorize('pickup_drop.delete');

        PickupDrop::findOrFail($id)->delete();

        Flux::toast(text: 'Pickup/Drop #'.$id.' deleted.', variant: 'success');
    }

    public function clearFilters(): void
    {
        $this->reset(['search', 'statusFilter', 'directionFilter', 'driverFilter', 'dateFrom', 'dateTo']);
        $this->resetPage();
    }

    #[Computed]
    public function drivers()
    {
        return EmployeeMaster::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']);
    }

    public function render()
    {
        $search = trim($this->search);

        $rows = PickupDrop::query()
            ->with([
                'customer:id,first_name,last_name,phone',
                'customerVehicle:id,registration_no',
                'driver:id,name',
                'vendor:id,name',
            ])
            ->when($search !== '', fn ($q) => $q->search($search))
            ->when($this->statusFilter !== 'all', fn ($q) => $q->where('status', $this->statusFilter))
            ->when($this->directionFilter !== 'all', fn ($q) => $q->where('direction', $this->directionFilter))
            ->when($this->driverFilter !== 'all', fn ($q) => $q->where('driver_employee_id', (int) $this->driverFilter))
            ->when($this->dateFrom !== '', fn ($q) => $q->whereDate('scheduled_at', '>=', $this->dateFrom))
            ->when($this->dateTo !== '', fn ($q) => $q->whereDate('scheduled_at', '<=', $this->dateTo))
            ->orderBy($this->sortBy, $this->sortDirection)
            ->tap(fn ($q) => $this->applyRecordScope($q))
            ->paginate(20);

        return view('pickup-drop::index', [
            'rows' => $rows,
            'statuses' => PickupDrop::statuses(),
            'directions' => PickupDrop::directions(),
        ]);
    }
}
