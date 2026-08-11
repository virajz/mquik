<?php

namespace App\Livewire;

use App\Modules\EmployeeMaster\Models\EmployeeMaster;
use App\Modules\NotificationCenter\Models\AppNotification;
use App\Support\ActingEmployee;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * The header bell: unread count and the latest few alerts for whoever is at
 * this screen. Also where the "who am I" choice is made, since that identity
 * drives the bench and the notification list too.
 */
class NotificationBell extends Component
{
    public ?int $employeeId = null;

    public function mount(): void
    {
        $this->employeeId = ActingEmployee::id();
    }

    public function updatedEmployeeId($value): void
    {
        ActingEmployee::set($value ? (int) $value : null);

        unset($this->recent, $this->unreadCount);

        $this->dispatch('acting-employee-changed');
    }

    #[On('notifications-changed')]
    public function refreshBell(): void
    {
        unset($this->recent, $this->unreadCount);
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

    #[Computed]
    public function recent()
    {
        if (! $this->employeeId) {
            return collect();
        }

        return AppNotification::query()
            ->forEmployee($this->employeeId)
            ->unread()
            ->orderByDesc('id')
            ->limit(5)
            ->get();
    }

    public function markRead(int $id): void
    {
        AppNotification::query()
            ->forEmployee($this->employeeId)
            ->whereKey($id)
            ->update(['read_at' => now()]);

        unset($this->recent, $this->unreadCount);
    }

    public function render()
    {
        return view('livewire.notification-bell');
    }
}
