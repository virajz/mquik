<?php

namespace App\Modules\AuthorizationMaster\Livewire;

use App\Models\User;
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

    #[On('authorization-master:user-roles-saved')]
    #[On('authorization-master:user-created')]
    public function refreshAfterSave(): void
    {
        // Triggers re-render.
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
