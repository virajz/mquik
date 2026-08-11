<?php

namespace App\Modules\NotificationCenter\Livewire;

use App\Modules\EmployeeMaster\Models\EmployeeMaster;
use App\Modules\NotificationCenter\Models\AppNotification;
use App\Support\ActingEmployee;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * The inbox. Like the technician bench, the person is picked on screen because
 * employees have no user accounts to log in as.
 */
#[Layout('layouts.app')]
#[Title('Notifications')]
class Index extends Component
{
    use WithPagination;

    #[Url(as: 'emp')]
    public ?int $employeeId = null;

    #[Url(as: 'unread')]
    public bool $unreadOnly = true;

    #[Url(as: 'type')]
    public string $typeFilter = 'all';

    public function mount(): void
    {
        // Fall back to whoever the header bell says is at this screen.
        $this->employeeId ??= ActingEmployee::id();
    }

    public function updatedEmployeeId($value): void
    {
        ActingEmployee::set($value ? (int) $value : null);
    }

    public function updating($name): void
    {
        if (in_array($name, ['employeeId', 'unreadOnly', 'typeFilter'], true)) {
            $this->resetPage();
        }
    }

    #[Computed]
    public function employees()
    {
        return EmployeeMaster::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']);
    }

    #[Computed]
    public function unreadCount(): int
    {
        return AppNotification::query()->forEmployee($this->employeeId)->unread()->count();
    }

    public function markRead(int $id): void
    {
        AppNotification::query()
            ->forEmployee($this->employeeId)
            ->whereKey($id)
            ->update(['read_at' => now()]);

        unset($this->unreadCount);
    }

    public function markAllRead(): void
    {
        AppNotification::query()
            ->forEmployee($this->employeeId)
            ->unread()
            ->update(['read_at' => now()]);

        unset($this->unreadCount);
    }

    public function render()
    {
        $rows = AppNotification::query()
            ->forEmployee($this->employeeId)
            ->when($this->unreadOnly, fn ($q) => $q->unread())
            ->when($this->typeFilter !== 'all', fn ($q) => $q->where('type', $this->typeFilter))
            ->with('actor:id,name')
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate(20);

        return view('notification-center::index', ['rows' => $rows]);
    }
}
