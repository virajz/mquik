<?php

namespace App\Modules\PickupDrop\Livewire;

use App\Concerns\ScopesToRecord;
use App\Modules\EmployeeMaster\Models\EmployeeMaster;
use App\Modules\PickupDrop\Models\PickupDrop;
use Flux\Flux;
use Illuminate\Support\Collection;
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

    /** Coarse stage cut: awaiting = car not yet in hand, collected = handover done. */
    #[Url(as: 'stage')]
    public string $stageFilter = 'all';

    public bool $showDriverBoard = false;

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
        $this->reset(['search', 'statusFilter', 'directionFilter', 'driverFilter', 'dateFrom', 'dateTo', 'stageFilter']);
        $this->resetPage();
    }

    /** One click on a board count narrows the table to that driver and stage. */
    public function focusDriver(int $driverId, string $stage): void
    {
        $this->driverFilter = (string) $driverId;
        $this->stageFilter = $stage;
        $this->statusFilter = 'all';
        $this->resetPage();
    }

    /** @return list<string> */
    public static function awaitingStatuses(): array
    {
        return [PickupDrop::STATUS_PENDING, PickupDrop::STATUS_DRIVER_ASSIGNED, PickupDrop::STATUS_DRIVER_ON_THE_WAY];
    }

    /** @return list<string> */
    public static function collectedStatuses(): array
    {
        return [PickupDrop::STATUS_VEHICLE_COLLECTED, PickupDrop::STATUS_VEHICLE_DELIVERED, PickupDrop::STATUS_COMPLETED];
    }

    /**
     * Per-driver workload: everything on their plate, what they have already
     * collected, and what is still waiting on them.
     *
     * @return Collection<int, array{id:int, name:string, assigned:int, collected:int, awaiting:int}>
     */
    #[Computed]
    public function driverBoard()
    {
        $awaiting = self::awaitingStatuses();
        $collected = self::collectedStatuses();

        return PickupDrop::query()
            ->whereNotNull('driver_employee_id')
            ->whereNull('cancelled_at')
            ->whereIn('status', [...$awaiting, ...$collected])
            ->with('driver:id,name')
            ->get(['id', 'driver_employee_id', 'status'])
            ->groupBy('driver_employee_id')
            ->map(fn ($jobs) => [
                'id' => $jobs->first()->driver_employee_id,
                'name' => $jobs->first()->driver?->name ?? '#'.$jobs->first()->driver_employee_id,
                'assigned' => $jobs->count(),
                'collected' => $jobs->whereIn('status', $collected)->count(),
                'awaiting' => $jobs->whereIn('status', $awaiting)->count(),
            ])
            ->sortBy('name')
            ->values();
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
                'pendingReason:id,name',
                'vendor:id,name',
            ])
            ->when($search !== '', fn ($q) => $q->search($search))
            ->when($this->statusFilter !== 'all', fn ($q) => $q->where('status', $this->statusFilter))
            ->when($this->directionFilter !== 'all', fn ($q) => $q->where('direction', $this->directionFilter))
            ->when($this->driverFilter !== 'all', fn ($q) => $q->where('driver_employee_id', (int) $this->driverFilter))
            ->when($this->stageFilter === 'awaiting', fn ($q) => $q->whereIn('status', self::awaitingStatuses()))
            ->when($this->stageFilter === 'collected', fn ($q) => $q->whereIn('status', self::collectedStatuses()))
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
