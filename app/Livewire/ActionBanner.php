<?php

namespace App\Livewire;

use App\Modules\NotificationCenter\Models\AppNotification;
use App\Support\ActingEmployee;
use App\Support\RolePreview;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * Standing alerts that will not go away until someone deals with them.
 *
 * These are the things a workshop cannot let scroll past: a delivery that has
 * slipped, parts that never arrived, an approval nobody chased. There is no
 * dismiss — the only way off the banner is to do the thing, or to explicitly
 * mark it handled, which is recorded against the user who did it.
 */
class ActionBanner extends Component
{
    /** Show everything, not just the first few. */
    public bool $expanded = false;

    #[On('notifications-changed')]
    #[On('acting-employee-changed')]
    #[On('role-preview-changed')]
    public function refreshBanner(): void
    {
        unset($this->alerts);
    }

    /**
     * Open alerts for whoever this screen belongs to.
     *
     * An admin previewing a role sees that role's alerts instead of their own,
     * which is the only way to check what a store manager is actually looking at.
     */
    #[Computed]
    public function alerts()
    {
        $role = RolePreview::role() ?: RolePreview::currentUserRole();
        $employeeId = RolePreview::isPreviewing() ? null : ActingEmployee::id();

        if (! $role && ! $employeeId) {
            return collect();
        }

        return AppNotification::query()
            ->needsAction()
            ->addressedTo($employeeId, $role)
            ->orderByRaw("case severity when 'critical' then 0 when 'warning' then 1 else 2 end")
            ->orderByDesc('id')
            ->limit(20)
            ->get();
    }

    /** Mark it handled — the only exit besides acting on it. */
    public function resolve(int $id): void
    {
        AppNotification::query()
            ->needsAction()
            ->whereKey($id)
            ->update([
                'resolved_at' => now(),
                'resolved_by_user_id' => auth()->id(),
                'read_at' => now(),
            ]);

        unset($this->alerts);
        $this->dispatch('notifications-changed');
    }

    public function render()
    {
        return view('livewire.action-banner');
    }
}
