<?php

namespace App\Modules\Appointment\Livewire;

use App\Concerns\ScopesToRecord;
use App\Modules\Appointment\Models\Appointment;
use App\Modules\BookingChannelMaster\Models\BookingChannelMaster;
use App\Modules\EmployeeMaster\Models\EmployeeMaster;
use App\Modules\PickupDropOptionMaster\Models\PickupDropOptionMaster;
use App\Modules\TimeSlotMaster\Models\TimeSlotMaster;
use App\Modules\WorkshopDepartmentMaster\Models\WorkshopDepartmentMaster;
use Flux\Flux;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Session;
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
    /** Defaults to bookings still in play; finished ones are the exception, not the view. */
    public string $statusFilter = 'open';

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

    /** Which date the from/to range applies to — when it is for, or when it was booked. */
    #[Url(as: 'dateon')]
    public string $dateField = 'appointment_at';

    #[Url(as: 'slot')]
    public string $slotFilter = 'all';

    #[Url(as: 'pdopt')]
    public string $pickupDropFilter = 'all';

    #[Url(as: 'sort')]
    public string $sortBy = 'appointment_at';

    #[Url(as: 'dir')]
    public string $sortDirection = 'desc';

    /** Row queued for deletion, driving the single shared confirm modal. */
    public ?int $deletingId = null;

    /**
     * Columns the reader can turn off, in the order they appear.
     *
     * No., When, Vehicle / Customer, Status and Actions are not listed: they are
     * what makes a row identifiable and actionable, so hiding them would leave a
     * table nobody can use.
     */
    public const OPTIONAL_COLUMNS = [
        'entry' => 'Entry',
        'job_card' => 'Job Card',
        'advisor' => 'Advisor / Dept',
        'channel' => 'Channel',
        'pickup_drop' => 'Pickup / Drop',
        'slots' => 'Time Slots',
        'priority' => 'Priority',
        'pending_reason' => 'Pending Reason',
    ];

    /**
     * Kept in the session rather than the URL: which columns someone likes is a
     * personal habit, not part of the search they would paste to a colleague.
     *
     * @var array<int, string>
     */
    #[Session(key: 'appointments.columns')]
    public array $visibleColumns = ['entry', 'job_card', 'advisor', 'channel', 'pickup_drop', 'slots', 'priority', 'pending_reason'];

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

    /**
     * One delete modal is shared by every row rather than rendered per row —
     * twenty dialogs per page is DOM the browser pays for on every render.
     */
    public function confirmDelete(int $id): void
    {
        $this->authorize('appointment.delete');

        $this->deletingId = $id;

        Flux::modal('appointment-delete')->show();
    }

    #[Computed]
    public function deleting(): ?Appointment
    {
        return $this->deletingId ? Appointment::find($this->deletingId) : null;
    }

    public function delete(?int $id = null): void
    {
        $this->authorize('appointment.delete');

        $id ??= $this->deletingId;

        if (! $id) {
            return;
        }

        Appointment::findOrFail($id)->delete();

        $this->deletingId = null;

        Flux::modal('appointment-delete')->close();

        Flux::toast(text: 'Appointment #'.$id.' deleted.', variant: 'success');
    }

    /** Whitelisted so the URL cannot point the range at an arbitrary column. */
    protected function dateColumn(): string
    {
        return $this->dateField === 'created_at' ? 'created_at' : 'appointment_at';
    }

    /**
     * The filters currently narrowing the list, as chips.
     *
     * Nine always-visible dropdowns read as noise and hide which ones are
     * actually doing something. The secondary filters live behind one button;
     * whatever is set shows here as a removable chip, so the applied state is
     * legible without opening anything.
     *
     * @return array<string, array{label: string, value: string}>
     */
    public function activeFilters(): array
    {
        $chips = [];

        if ($this->statusFilter !== 'open') {
            $chips['statusFilter'] = [
                'label' => 'Status',
                'value' => $this->statusFilter === 'all' ? 'All' : (Appointment::statuses()[$this->statusFilter] ?? $this->statusFilter),
            ];
        }

        if ($this->channelFilter !== 'all') {
            $chips['channelFilter'] = ['label' => 'Channel', 'value' => (string) BookingChannelMaster::whereKey($this->channelFilter)->value('name')];
        }

        if ($this->advisorFilter !== 'all') {
            $chips['advisorFilter'] = ['label' => 'Advisor', 'value' => (string) EmployeeMaster::whereKey($this->advisorFilter)->value('name')];
        }

        if ($this->deptFilter !== 'all') {
            $chips['deptFilter'] = ['label' => 'Department', 'value' => (string) WorkshopDepartmentMaster::whereKey($this->deptFilter)->value('name')];
        }

        if ($this->slotFilter !== 'all') {
            $chips['slotFilter'] = ['label' => 'Time slot', 'value' => (string) TimeSlotMaster::whereKey($this->slotFilter)->value('name')];
        }

        if ($this->pickupDropFilter !== 'all') {
            $chips['pickupDropFilter'] = ['label' => 'Pickup/drop', 'value' => (string) PickupDropOptionMaster::whereKey($this->pickupDropFilter)->value('name')];
        }

        if ($this->dateFrom !== '' || $this->dateTo !== '') {
            $on = $this->dateField === 'created_at' ? 'Created' : 'Appointment';
            $range = trim(($this->dateFrom ?: '…').' → '.($this->dateTo ?: '…'));
            $chips['dateRange'] = ['label' => $on.' date', 'value' => $range];
        }

        return $chips;
    }

    public function removeFilter(string $key): void
    {
        match ($key) {
            'statusFilter' => $this->statusFilter = 'open',
            'dateRange' => $this->reset(['dateFrom', 'dateTo', 'dateField']),
            'channelFilter', 'advisorFilter', 'deptFilter', 'slotFilter', 'pickupDropFilter' => $this->{$key} = 'all',
            default => null,
        };

        $this->resetPage();
    }

    /** Whether a toggleable column is currently on. */
    public function showsColumn(string $key): bool
    {
        return in_array($key, $this->visibleColumns, true);
    }

    /** Visible columns plus the five that are always on, for the empty-state cell. */
    #[Computed]
    public function columnCount(): int
    {
        return count($this->visibleColumns) + 5;
    }

    public function resetColumns(): void
    {
        $this->visibleColumns = array_keys(self::OPTIONAL_COLUMNS);
    }

    public function clearFilters(): void
    {
        $this->reset(['search', 'statusFilter', 'channelFilter', 'advisorFilter', 'deptFilter', 'dateFrom', 'dateTo', 'dateField', 'slotFilter', 'pickupDropFilter']);
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

    #[Computed]
    public function timeSlots()
    {
        return TimeSlotMaster::query()->where('is_active', true)->orderBy('slot_start_time')->get(['id', 'name']);
    }

    #[Computed]
    public function pickupDropOptions()
    {
        return PickupDropOptionMaster::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']);
    }

    public function render()
    {
        $search = trim($this->search);

        $rows = Appointment::query()
            ->with([
                'customer:id,first_name,last_name,phone',
                'customerVehicle:id,registration_no,model_id',
                'customerVehicle.model:id,name',
                'workshopDepartment:id,name',
                'advisor:id,name',
                'serviceType:id,name',
                'bookingChannel:id,name',
                'timeSlot:id,name,slot_start_time,slot_end_time',
                'dropTimeSlot:id,name,slot_start_time,slot_end_time',
                'priority:id,name',
                'pickupDropOption:id,name',
                'pendingReason:id,name',
                // Driver stages are derived from the linked Pickup/Drop job.
                'pickupDrops:id,appointment_id,status,driver_employee_id,pickup_drop_no',
                // The job card the booking became, shown as a link in the list.
                'jobCards:id,appointment_id,job_card_no',
            ])
            ->when($search !== '', fn ($q) => $q->search($search))
            // 'open' is the group of everything unfinished; 'pending' below is the
            // literal status, and the two must not share a value.
            ->when($this->statusFilter === 'open', fn ($q) => $q->whereIn('status', Appointment::pendingStatuses()))
            ->when(! in_array($this->statusFilter, ['all', 'open'], true), fn ($q) => $q->where('status', $this->statusFilter))
            ->when($this->channelFilter !== 'all', fn ($q) => $q->where('booking_channel_id', (int) $this->channelFilter))
            ->when($this->advisorFilter !== 'all', fn ($q) => $q->where('assigned_advisor_id', (int) $this->advisorFilter))
            ->when($this->deptFilter !== 'all', fn ($q) => $q->where('workshop_department_id', (int) $this->deptFilter))
            ->when($this->dateFrom !== '', fn ($q) => $q->whereDate($this->dateColumn(), '>=', $this->dateFrom))
            ->when($this->dateTo !== '', fn ($q) => $q->whereDate($this->dateColumn(), '<=', $this->dateTo))
            // A slot can be booked on either leg, so match either.
            ->when($this->slotFilter !== 'all', fn ($q) => $q->where(fn ($w) => $w
                ->where('time_slot_id', (int) $this->slotFilter)
                ->orWhere('drop_time_slot_id', (int) $this->slotFilter)))
            ->when($this->pickupDropFilter !== 'all', fn ($q) => $q->where('pickup_drop_option_id', (int) $this->pickupDropFilter))
            ->orderBy($this->sortBy, $this->sortDirection)
            ->tap(fn ($q) => $this->applyRecordScope($q))
            ->paginate(20);

        return view('appointment::index', [
            'optionalColumns' => self::OPTIONAL_COLUMNS,
            'rows' => $rows,
            'statuses' => Appointment::statuses(),
            'channels' => BookingChannelMaster::query()->where('is_active', true)
                ->orderBy('name')->pluck('name', 'id'),
        ]);
    }
}
