<?php

namespace App\Livewire;

use App\Modules\NotificationCenter\Models\AppNotification;
use App\Support\RolePreview;
use Livewire\Attributes\Computed;
use Livewire\Component;

/**
 * The admin's "show me what they see" control.
 *
 * Sits bottom-right and only exists for Super Admin, who otherwise has no way
 * to tell whose alerts are whose because they hold every permission.
 */
class RolePreviewSwitcher extends Component
{
    public ?string $previewRole = null;

    public function mount(): void
    {
        $this->previewRole = RolePreview::role();
    }

    public function updatedPreviewRole($value): void
    {
        RolePreview::set($value ?: null);

        unset($this->counts);

        // A full (wire:navigate) repaint is the reliable way to get the banner,
        // bell and page all reflecting the switch at once.
        $this->redirect(url()->current(), navigate: true);
    }

    public function clear(): void
    {
        $this->previewRole = null;
        RolePreview::set(null);

        unset($this->counts);

        $this->redirect(url()->current(), navigate: true);
    }

    /** @return list<string> */
    #[Computed]
    public function roles(): array
    {
        return RolePreview::selectableRoles();
    }

    /**
     * Open action items per role, so the admin can see where the pile-up is
     * before switching into it.
     *
     * @return array<string, int>
     */
    #[Computed]
    public function counts(): array
    {
        return AppNotification::query()
            ->needsAction()
            ->whereNotNull('role')
            ->selectRaw('role, count(*) as total')
            ->groupBy('role')
            ->pluck('total', 'role')
            ->all();
    }

    public function render()
    {
        return view('livewire.role-preview-switcher');
    }
}
