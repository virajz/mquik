<?php

namespace App\Modules\AuthorizationMaster\Livewire;

use Flux\Flux;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use Spatie\Permission\Models\Role;

#[Layout('layouts.app')]
#[Title('Roles & Permissions')]
class Index extends Component
{
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url(as: 'sort')]
    public string $sortBy = 'name';

    #[Url(as: 'dir')]
    public string $sortDirection = 'asc';

    /** Whitelist sortable columns — never trust the URL */
    protected array $sortable = ['id', 'name', 'created_at'];

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

    public function openCreate(): void
    {
        $this->dispatch('authorization-master:edit', id: null);
        Flux::modal('authorization-master-form')->show();
    }

    public function openEdit(int $id): void
    {
        $this->dispatch('authorization-master:edit', id: $id);
        Flux::modal('authorization-master-form')->show();
    }

    #[On('authorization-master:saved')]
    public function refreshAfterSave(): void
    {
        // Triggers re-render; pagination cursor preserved.
    }

    public function delete(int $id): void
    {
        $this->authorize('authorization_master.delete');

        $role = Role::query()->withCount('users')->find($id);

        if (! $role) {
            Flux::toast(text: 'Role not found.', variant: 'danger');

            return;
        }

        if ($role->name === 'Super Admin') {
            Flux::toast(
                text: 'The Super Admin role is a system role and cannot be deleted.',
                variant: 'danger',
            );

            return;
        }

        if ($role->users_count > 0) {
            Flux::toast(
                text: 'Cannot delete role — it is still assigned to '.$role->users_count.' user(s).',
                variant: 'danger',
            );

            return;
        }

        $role->delete();
        Flux::toast(text: 'Role #'.$id.' deleted.', variant: 'success');
    }

    public function render()
    {
        $search = $this->search;

        $rows = Role::query()
            ->withCount(['permissions', 'users'])
            ->when($search !== '', function ($q) use ($search) {
                $q->whereLike('name', '%'.$search.'%', caseSensitive: false);
            })
            ->orderBy($this->sortBy, $this->sortDirection)
            ->paginate(20);

        return view('authorization-master::index', ['rows' => $rows]);
    }
}
