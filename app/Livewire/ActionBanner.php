<?php

namespace App\Livewire;

use App\Modules\NotificationCenter\Models\AppNotification;
use App\Support\ActingEmployee;
use App\Support\RolePreview;
use Flux\Flux;
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

    /** The alert awaiting confirmation in the modal. */
    public ?int $pendingId = null;

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
        // Switched off for this environment — see config/mquik.php. The alerts
        // are still recorded; only this strip is hidden.
        if (! config('mquik.features.alert_banner', true)) {
            return collect();
        }

        $query = AppNotification::query()
            ->needsAction()
            ->orderByRaw("case severity when 'critical' then 0 when 'warning' then 1 else 2 end")
            ->orderByDesc('id')
            ->limit(20);

        // Previewing a role: show exactly that role's pile.
        if (RolePreview::isPreviewing()) {
            return $query->where('role', RolePreview::role())->get();
        }

        // An admin is nobody's job, so nothing is addressed to them. Rather than
        // showing an empty banner, show the whole workshop's open items with a
        // role badge on each — the overview only they need.
        if (RolePreview::isAvailable()) {
            return $query->get();
        }

        $role = RolePreview::currentUserRole();
        $employeeId = ActingEmployee::id();

        if (! $role && ! $employeeId) {
            return collect();
        }

        return $query->addressedTo($employeeId, $role)->get();
    }

    /** Ask before clearing — this removes the alert for everyone. */
    public function confirmResolve(int $id): void
    {
        $this->pendingId = $id;

        Flux::modal('confirm-handled')->show();
    }

    /** The alert being confirmed, so the modal can name it. */
    #[Computed]
    public function pendingAlert(): ?AppNotification
    {
        return $this->pendingId ? AppNotification::find($this->pendingId) : null;
    }

    /** Mark it handled — the only exit besides acting on it. */
    public function resolve(): void
    {
        if (! $this->pendingId) {
            return;
        }

        AppNotification::query()
            ->needsAction()
            ->whereKey($this->pendingId)
            ->update([
                'resolved_at' => now(),
                'resolved_by_user_id' => auth()->id(),
                'read_at' => now(),
            ]);

        $this->pendingId = null;

        unset($this->alerts, $this->pendingAlert);

        Flux::modal('confirm-handled')->close();
        Flux::toast(text: 'Marked as handled.', variant: 'success');

        $this->dispatch('notifications-changed');
    }

    public function render()
    {
        return view('livewire.action-banner');
    }
}
