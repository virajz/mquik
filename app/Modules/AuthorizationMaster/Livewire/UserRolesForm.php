<?php

namespace App\Modules\AuthorizationMaster\Livewire;

use App\Models\User;
use Flux\Flux;
use Livewire\Attributes\On;
use Livewire\Component;
use Spatie\Permission\Models\Role;

class UserRolesForm extends Component
{
    public ?int $userId = null;

    public string $userName = '';

    /** Whichever identifier the user has — email is optional now that a mobile alone can log in. */
    public string $userContact = '';

    /** @var array<int, int> */
    public array $selectedRoles = [];

    #[On('authorization-master:manage-user')]
    public function load(int $id): void
    {
        $this->resetForm();

        $user = User::with('roles')->findOrFail($id);
        $this->userId = $user->id;
        $this->userName = $user->name;
        $this->userContact = $user->email ?? ($user->phone ? '+91 '.$user->phone : '');
        $this->selectedRoles = $user->roles->pluck('id')->map(fn ($v) => (int) $v)->all();
    }

    public function save(): void
    {
        if ($this->userId === null) {
            return;
        }

        $user = User::findOrFail($this->userId);
        $roleNames = Role::query()
            ->whereIn('id', $this->selectedRoles)
            ->pluck('name')
            ->all();

        $user->syncRoles($roleNames);

        Flux::toast(
            text: 'Roles updated for '.$user->name.'.',
            variant: 'success',
        );

        $this->dispatch('authorization-master:user-roles-saved');
        $this->resetForm();
        Flux::modal('authorization-master-user-roles')->close();
    }

    protected function resetForm(): void
    {
        $this->userId = null;
        $this->userName = '';
        $this->userContact = '';
        $this->selectedRoles = [];
    }

    public function render()
    {
        return view('authorization-master::user-roles-form', [
            'roles' => Role::query()->orderBy('name')->get(),
        ]);
    }
}
