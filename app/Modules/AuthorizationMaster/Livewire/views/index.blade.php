<div>
    {{-- Page heading + tabs + primary action --}}
    <div class="mb-6 flex items-start justify-between gap-4">
        <div>
            <flux:heading size="xl" level="1">Roles & Permissions</flux:heading>
            <flux:text class="mt-1">Define what each role can see and do across the workshop.</flux:text>
        </div>

        <div class="flex items-center gap-2">
            <div class="flex items-center gap-1 rounded-lg bg-zinc-100 dark:bg-zinc-800 p-1">
                <flux:button size="sm" variant="primary" icon="shield-check">Roles</flux:button>
                <flux:button size="sm" variant="ghost" icon="users" :href="route('authorization-master.users')" wire:navigate>
                    Users
                </flux:button>
            </div>

            @can('authorization_master.create')
                <flux:button variant="primary" icon="plus" wire:click="openCreate">
                New Role
            </flux:button>
            @endcan
        </div>
    </div>

    {{-- Filter bar --}}
    <div class="mb-4 flex items-center gap-3">
        <flux:input
            wire:model.live.debounce.300ms="search"
            placeholder="Search by role name..."
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
                Role Name
            </flux:table.column>
            <flux:table.column class="w-32">Permissions</flux:table.column>
            <flux:table.column class="w-32">Members</flux:table.column>
            <flux:table.column class="w-28">System</flux:table.column>
            <flux:table.column class="w-32" align="end">Actions</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @forelse ($rows as $row)
                <flux:table.row :key="$row->id">
                    <flux:table.cell class="font-mono text-xs text-zinc-500">
                        #{{ str_pad($row->id, 5, '0', STR_PAD_LEFT) }}
                    </flux:table.cell>

                    <flux:table.cell class="font-medium">{{ $row->name }}</flux:table.cell>

                    <flux:table.cell class="text-zinc-600 dark:text-zinc-300">
                        {{ $row->permissions_count }}
                    </flux:table.cell>

                    <flux:table.cell class="text-zinc-600 dark:text-zinc-300">
                        {{ $row->users_count }}
                    </flux:table.cell>

                    <flux:table.cell>
                        @if ($row->name === 'Super Admin')
                            <flux:badge color="lime" size="sm">System</flux:badge>
                        @else
                            <span class="text-zinc-400 text-xs">—</span>
                        @endif
                    </flux:table.cell>

                    <flux:table.cell>
                        <div class="flex items-center justify-end gap-1">
                            @can('authorization_master.update')
                                <flux:button size="sm" variant="ghost" icon="pencil-square"
                                wire:click="openEdit({{ $row->id }})">Edit</flux:button>
                            @endcan
                            @if ($row->name !== 'Super Admin')
                                @can('authorization_master.delete')
                                    <flux:modal.trigger :name="'authorization-master-delete-' . $row->id">
                                        <flux:button size="sm" variant="ghost" icon="trash" />
                                    </flux:modal.trigger>
                                    <flux:modal :name="'authorization-master-delete-' . $row->id">
                                        <div class="space-y-4">
                                            <flux:heading size="lg">Delete role "{{ $row->name }}"?</flux:heading>
                                            <flux:text>
                                                Cannot be undone. If any users are assigned this role the delete will fail
                                                and you'll see a warning.
                                            </flux:text>
                                            <div class="flex gap-2 justify-end">
                                                <flux:modal.close>
                                                    <flux:button variant="ghost">Cancel</flux:button>
                                                </flux:modal.close>
                                                <flux:button variant="danger"
                                                    wire:click="delete({{ $row->id }})"
                                                    x-on:click="$flux.modal('authorization-master-delete-{{ $row->id }}').close()">
                                                    Delete
                                                </flux:button>
                                            </div>
                                        </div>
                                    </flux:modal>
                                @endcan
                            @endif
                        </div>
                    </flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="6" class="text-center text-zinc-500 py-12">
                        <flux:icon.shield-check class="mx-auto mb-3 size-8 text-zinc-400" />
                        <div class="font-medium">No roles yet</div>
                        <flux:text class="mt-1">Create roles like Manager, Mechanic, Receptionist to grant scoped access.</flux:text>
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

    <livewire:authorization-master.form />
</div>
