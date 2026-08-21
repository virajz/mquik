<?php

namespace App\Modules\AuthorizationMaster\Livewire;

use App\Models\User;
use App\Support\Otp\OtpService;
use Flux\Flux;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('Users')]
class Users extends Component
{
    /** Shown only while SMS delivery is mocked — the OTP, never a password. */
    public ?string $mockCode = null;

    public ?string $mockCodeFor = null;

    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url(as: 'sort')]
    public string $sortBy = 'name';

    #[Url(as: 'dir')]
    public string $sortDirection = 'asc';

    /** Whitelist sortable columns — never trust the URL */
    protected array $sortable = ['id', 'name', 'email', 'created_at'];

    public function updatingSearch(): void
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

    public function openManageRoles(int $id): void
    {
        $this->dispatch('authorization-master:manage-user', id: $id);
        Flux::modal('authorization-master-user-roles')->show();
    }

    public function openCreate(): void
    {
        $this->authorize('authorization_master.create');
        $this->dispatch('authorization-master:create-user');
        Flux::modal('authorization-master-user-form')->show();
    }

    public function toggleActive(int $id): void
    {
        $this->authorize('authorization_master.update');

        $user = User::findOrFail($id);

        if ($user->id === auth()->id()) {
            Flux::toast(text: 'You cannot deactivate your own account.', variant: 'danger');

            return;
        }

        // Don't let the workshop end up with no active Super Admin.
        if ($user->is_active && $user->hasRole('Super Admin') && $this->lastActiveSuperAdmin()) {
            Flux::toast(
                text: 'Cannot deactivate the only active Super Admin. Promote someone else first.',
                variant: 'danger',
            );

            return;
        }

        $user->forceFill(['is_active' => ! $user->is_active])->save();

        Flux::toast(
            text: $user->name.' '.($user->is_active ? 'reactivated' : 'deactivated').'.',
            variant: 'success',
        );
    }

    public function delete(int $id): void
    {
        $this->authorize('authorization_master.delete');

        $user = User::findOrFail($id);

        if ($user->id === auth()->id()) {
            Flux::toast(text: 'You cannot delete your own account.', variant: 'danger');

            return;
        }

        if ($user->hasRole('Super Admin') && $this->lastActiveSuperAdmin()) {
            Flux::toast(
                text: 'Cannot delete the only active Super Admin. Assign someone else the role first.',
                variant: 'danger',
            );

            return;
        }

        $name = $user->name;
        $user->delete();
        Flux::toast(text: $name.' deleted.', variant: 'success');
    }

    /** True if exactly one active Super Admin exists in the whole system. */
    protected function lastActiveSuperAdmin(): bool
    {
        return User::active()
            ->whereHas('roles', fn ($q) => $q->where('name', 'Super Admin'))
            ->count() <= 1;
    }

    #[On('authorization-master:user-roles-saved')]
    #[On('authorization-master:user-created')]
    public function refreshAfterSave(): void
    {
        // Triggers re-render.
    }

    /**
     * Send the user a code to set a new password.
     *
     * An admin can trigger a reset but never sees or sets the password — the
     * user chooses it themselves from the code.
     */
    public function sendPasswordReset(int $userId, OtpService $otp): void
    {
        $this->authorize('authorization_master.update');

        $user = User::findOrFail($userId);

        if (! $user->phone) {
            Flux::toast(text: $user->name.' has no phone number on file. Add one first.', variant: 'warning');

            return;
        }

        $user->forceFill(['must_reset_password' => true])->save();

        $this->mockCode = $otp->issue($user);
        $this->mockCodeFor = $user->name;

        Flux::toast(
            text: 'Reset code sent to '.$user->name.' on +91 '.$user->phone.'.',
            variant: 'success',
        );
    }

    public function dismissMockCode(): void
    {
        $this->mockCode = null;
        $this->mockCodeFor = null;
    }

    public function render()
    {
        $search = $this->search;

        $rows = User::query()
            ->with('roles')
            ->when($search !== '', function ($q) use ($search) {
                $q->where(function ($query) use ($search) {
                    $term = '%'.$search.'%';
                    $query->whereLike('name', $term, caseSensitive: false)
                        ->orWhereLike('email', $term, caseSensitive: false);
                });
            })
            ->orderBy($this->sortBy, $this->sortDirection)
            ->paginate(20);

        return view('authorization-master::users', ['rows' => $rows]);
    }
}
