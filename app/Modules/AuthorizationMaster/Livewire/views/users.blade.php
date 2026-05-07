<div>
    {{-- Page heading + tabs --}}
    <div class="mb-6 flex items-start justify-between gap-4">
        <div>
            <flux:heading size="xl" level="1">Users</flux:heading>
            <flux:text class="mt-1">Assign roles to existing users to control what they can access.</flux:text>
        </div>

        <div class="flex items-center gap-2">
            <div class="flex items-center gap-1 rounded-lg bg-zinc-100 dark:bg-zinc-800 p-1">
                <flux:button size="sm" variant="ghost" icon="shield-check" :href="route('authorization-master.index')" wire:navigate>
                    Roles
                </flux:button>
                <flux:button size="sm" variant="primary" icon="users">Users</flux:button>
            </div>

            @can('authorization_master.create')
                <flux:button variant="primary" icon="plus" wire:click="openCreate">New User</flux:button>
            @endcan
        </div>
    </div>

    {{-- Filter bar --}}
    <div class="mb-4 flex items-center gap-3">
        <flux:input
            wire:model.live.debounce.300ms="search"
            placeholder="Search by name or email..."
            icon="magnifying-glass"
            clearable
            class="max-w-sm"
        />
    </div>

    {{-- Table --}}
    <flux:table>
        <flux:table.columns>
            <flux:table.column class="w-20" sortable :sorted="$sortBy === 'id'" :direction="$sortDirection" wire:click="sort('id')">
                ID
            </flux:table.column>
            <flux:table.column sortable :sorted="$sortBy === 'name'" :direction="$sortDirection" wire:click="sort('name')">
                Name
            </flux:table.column>
            <flux:table.column sortable :sorted="$sortBy === 'email'" :direction="$sortDirection" wire:click="sort('email')">
                Email
            </flux:table.column>
            <flux:table.column>Roles</flux:table.column>
            <flux:table.column class="w-40" sortable :sorted="$sortBy === 'created_at'" :direction="$sortDirection" wire:click="sort('created_at')">
                Joined
            </flux:table.column>
            <flux:table.column class="w-40" align="end">Actions</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @forelse ($rows as $row)
                <flux:table.row :key="$row->id">
                    <flux:table.cell class="font-mono text-xs text-zinc-500">
                        #{{ str_pad($row->id, 5, '0', STR_PAD_LEFT) }}
                    </flux:table.cell>

                    <flux:table.cell class="font-medium">{{ $row->name }}</flux:table.cell>

                    <flux:table.cell class="text-zinc-600 dark:text-zinc-300">
                        {{ $row->email }}
                    </flux:table.cell>

                    <flux:table.cell>
                        @if ($row->roles->isEmpty())
                            <span class="text-zinc-400 text-xs">No roles</span>
                        @else
                            <div class="flex flex-wrap gap-1">
                                @foreach ($row->roles as $role)
                                    <flux:badge color="zinc" size="sm">{{ $role->name }}</flux:badge>
                                @endforeach
                            </div>
                        @endif
                    </flux:table.cell>

                    <flux:table.cell class="text-zinc-500 text-xs">
                        {{ $row->created_at?->format('d M Y') ?? '—' }}
                    </flux:table.cell>

                    <flux:table.cell>
                        <div class="flex items-center justify-end gap-1">
                            <flux:button size="sm" variant="ghost" icon="user-plus"
                                wire:click="openManageRoles({{ $row->id }})">
                                Manage Roles
                            </flux:button>
                        </div>
                    </flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="6" class="text-center text-zinc-500 py-12">
                        <flux:icon.users class="mx-auto mb-3 size-8 text-zinc-400" />
                        <div class="font-medium">No users yet</div>
                        <flux:text class="mt-1">Users will appear here once they sign up.</flux:text>
                    </flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>

    @if ($rows->hasPages())
        <div class="mt-4">
            <flux:pagination :paginator="$rows" />
        </div>
    @endif

    <livewire:authorization-master.user-roles-form />
    @can('authorization_master.create')
        <livewire:authorization-master.user-form wire:key="authorization-master-user-form" />
    @endcan
</div>
