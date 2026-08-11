<?php

namespace App\Modules\Appointment\Livewire;

use App\Concerns\ScopesToRecord;
use App\Modules\Appointment\Models\Appointment;
use App\Modules\BookingChannelMaster\Models\BookingChannelMaster;
use App\Modules\EmployeeMaster\Models\EmployeeMaster;
use App\Modules\WorkshopDepartmentMaster\Models\WorkshopDepartmentMaster;
use Flux\Flux;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('Appointments')]
class Index extends Component
{
    use ScopesToRecord;
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url(as: 'status')]
    public string $statusFilter = 'all';

    #[Url(as: 'channel')]
    public string $channelFilter = 'all';

    #[Url(as: 'advisor')]
    public string $advisorFilter = 'all';

    #[Url(as: 'dept')]
    public string $deptFilter = 'all';

    #[Url(as: 'from')]
    public string $dateFrom = '';

    #[Url(as: 'to')]
    public string $dateTo = '';

    #[Url(as: 'sort')]
    public string $sortBy = 'appointment_at';

    #[Url(as: 'dir')]
    public string $sortDirection = 'desc';

    /** Whitelist sortable columns to prevent SQL injection via the URL */
    protected array $sortable = ['id', 'appointment_no', 'appointment_at', 'status', 'created_at'];

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatingChannelFilter(): void
    {
        $this->resetPage();
    }

    public function updatingAdvisorFilter(): void
    {
        $this->resetPage();
    }

    public function updatingDeptFilter(): void
    {
        $this->resetPage();
    }

    public function updatingDateFrom(): void
    {
        $this->resetPage();
    }

    public function updatingDateTo(): void
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
        $this->authorize('appointment.delete');

        Appointment::findOrFail($id)->delete();

        Flux::toast(text: 'Appointment #'.$id.' deleted.', variant: 'success');
    }

    public function clearFilters(): void
    {
        $this->reset(['search', 'statusFilter', 'channelFilter', 'advisorFilter', 'deptFilter', 'dateFrom', 'dateTo']);
        $this->resetPage();
    }

    #[Computed]
    public function advisors()
    {
        return EmployeeMaster::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']);
    }

    #[Computed]
    public function departments()
    {
        return WorkshopDepartmentMaster::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']);
    }

    public function render()
    {
        $search = trim($this->search);

        $rows = Appointment::query()
            ->with([
                'customer:id,first_name,last_name,phone',
                'customerVehicle:id,registration_no,model_id',
                'customerVehicle.model:id,name,brand_id',
                'customerVehicle.model.brand:id,name',
                'workshopDepartment:id,name',
                'advisor:id,name',
                'serviceType:id,name',
                'bookingChannel:id,name',
                'timeSlot:id,name,slot_start_time,slot_end_time',
                'priority:id,name',
                'pickupDropOption:id,name',
                // Driver stages are derived from the linked Pickup/Drop job.
                'pickupDrops:id,appointment_id,status,driver_employee_id',
            ])
            ->when($search !== '', fn ($q) => $q->search($search))
            ->when($this->statusFilter !== 'all', fn ($q) => $q->where('status', $this->statusFilter))
            ->when($this->channelFilter !== 'all', fn ($q) => $q->where('booking_channel_id', (int) $this->channelFilter))
            ->when($this->advisorFilter !== 'all', fn ($q) => $q->where('assigned_advisor_id', (int) $this->advisorFilter))
            ->when($this->deptFilter !== 'all', fn ($q) => $q->where('workshop_department_id', (int) $this->deptFilter))
            ->when($this->dateFrom !== '', fn ($q) => $q->whereDate('appointment_at', '>=', $this->dateFrom))
            ->when($this->dateTo !== '', fn ($q) => $q->whereDate('appointment_at', '<=', $this->dateTo))
            ->orderBy($this->sortBy, $this->sortDirection)
            ->tap(fn ($q) => $this->applyRecordScope($q))
            ->paginate(20);

        return view('appointment::index', [
            'rows' => $rows,
            'statuses' => Appointment::statuses(),
            'channels' => BookingChannelMaster::query()->where('is_active', true)
                ->orderBy('name')->pluck('name', 'id'),
        ]);
    }
}
