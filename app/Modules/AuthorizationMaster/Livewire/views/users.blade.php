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
            placeholder="Search by user ID, username, name, phone or email…"
            icon="magnifying-glass"
            clearable
            class="max-w-sm"
        />
    </div>

    {{-- Table --}}
    @if ($mockCode)
        <flux:callout variant="warning" icon="beaker" heading="SMS is mocked in this environment" class="mb-4">
            <div class="mt-2 flex flex-wrap items-center gap-3">
                <flux:text size="sm">Code for {{ $mockCodeFor }}:</flux:text>
                <flux:input value="{{ $mockCode }}" readonly copyable class="w-40"
                    class:input="font-mono text-center tracking-[0.3em]" />
                <flux:button size="sm" variant="ghost" wire:click="dismissMockCode">Dismiss</flux:button>
            </div>
        </flux:callout>
    @endif

    <flux:table>
        <flux:table.columns>
            <flux:table.column class="w-14">Sr. No.</flux:table.column>
            <flux:table.column class="w-44" sortable :sorted="$sortBy === 'id'" :direction="$sortDirection" wire:click="sort('id')">
                User ID
            </flux:table.column>
            <flux:table.column sortable :sorted="$sortBy === 'name'" :direction="$sortDirection" wire:click="sort('name')">
                Name
            </flux:table.column>
            <flux:table.column sortable :sorted="$sortBy === 'email'" :direction="$sortDirection" wire:click="sort('email')">
                Email
            </flux:table.column>
            <flux:table.column>Phone</flux:table.column>
            <flux:table.column>Roles</flux:table.column>
            <flux:table.column class="w-24">Status</flux:table.column>
            <flux:table.column class="w-40" sortable :sorted="$sortBy === 'created_at'" :direction="$sortDirection" wire:click="sort('created_at')">
                Created
            </flux:table.column>
            <flux:table.column class="w-32" align="end">Actions</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @forelse ($rows as $row)
                <flux:table.row :key="$row->id">
                    {{-- Position on the page, not the id — what people count down. --}}
                    <flux:table.cell class="text-xs text-zinc-500 tabular-nums">
                        {{ $rows->firstItem() + $loop->index }}
                    </flux:table.cell>

                    <flux:table.cell class="font-mono text-xs text-zinc-500">
                        {{ $row->user_code ?? '—' }}
                    </flux:table.cell>

                    <flux:table.cell class="font-medium">
                        {{ $row->name }}
                        @if ($row->username)
                            <div class="text-xs text-zinc-500 font-mono mt-0.5">{{ $row->username }}</div>
                        @endif
                    </flux:table.cell>

                    <flux:table.cell class="text-zinc-600 dark:text-zinc-300">
                        {{ $row->email ?: '—' }}
                    </flux:table.cell>

                    <flux:table.cell class="text-zinc-600 dark:text-zinc-300">
                        @if ($row->phone)
                            <a href="tel:{{ $row->phone }}" class="hover:underline">+91 {{ $row->phone }}</a>
                            @if ($row->must_reset_password)
                                <flux:badge color="amber" size="sm" class="ml-1">Password not set</flux:badge>
                            @endif
                        @else
                            <span class="text-zinc-400 text-xs">Not set</span>
                        @endif
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

                    <flux:table.cell>
                        @if ($row->is_active)
                            <flux:badge color="lime" size="sm">Active</flux:badge>
                        @else
                            <flux:badge color="zinc" size="sm">Inactive</flux:badge>
                        @endif
                    </flux:table.cell>

                    <flux:table.cell class="text-zinc-500 text-xs">
                        {{ $row->created_at?->format('d/m/Y') ?? '—' }}
                    </flux:table.cell>

                    <flux:table.cell>
                        <div class="flex items-center justify-end gap-1">
                            <flux:button size="sm" variant="ghost" icon="user-plus"
                                wire:click="openManageRoles({{ $row->id }})">
                                Manage
                            </flux:button>

                            @can('authorization_master.update')
                                <flux:tooltip content="Edit name, email or phone">
                                    <flux:button size="sm" variant="ghost" icon="pencil-square"
                                        wire:click="$dispatch('authorization-master:edit-user', { id: {{ $row->id }} })" />
                                </flux:tooltip>
                            @endcan

                            @can('authorization_master.update')
                                {{-- Sends a code; the user picks their own password.
                                     An admin never sees or sets one. --}}
                                <flux:tooltip content="Text them a code to set a new password">
                                    <flux:button size="sm" variant="ghost" icon="key"
                                        wire:click="sendPasswordReset({{ $row->id }})" />
                                </flux:tooltip>
                            @endcan

                            @if ($row->id !== auth()->id() && (auth()->user()->can('authorization_master.update') || auth()->user()->can('authorization_master.delete')))
                                <flux:dropdown align="end">
                                    <flux:button size="sm" variant="ghost" icon="ellipsis-vertical" />
                                    <flux:menu>
                                        @can('authorization_master.update')
                                            <flux:menu.item
                                                :icon="$row->is_active ? 'no-symbol' : 'check-circle'"
                                                wire:click="toggleActive({{ $row->id }})"
                                            >
                                                {{ $row->is_active ? 'Deactivate' : 'Activate' }}
                                            </flux:menu.item>
                                        @endcan
                                        @can('authorization_master.delete')
                                            <flux:menu.separator />
                                            <flux:modal.trigger :name="'authorization-master-user-delete-'.$row->id">
                                                <flux:menu.item icon="trash" variant="danger">Delete user</flux:menu.item>
                                            </flux:modal.trigger>
                                        @endcan
                                    </flux:menu>
                                </flux:dropdown>
                            @endif

                            @can('authorization_master.delete')
                                @if ($row->id !== auth()->id())
                                    <flux:modal :name="'authorization-master-user-delete-'.$row->id">
                                        <div class="space-y-4">
                                            <flux:heading size="lg">Delete {{ $row->name }}?</flux:heading>
                                            <flux:text>
                                                This permanently removes the account and revokes all roles.
                                                The audit history of what they did is preserved.
                                                Consider <strong>Deactivate</strong> instead if you may need to restore access.
                                            </flux:text>
                                            <div class="flex gap-2 justify-end">
                                                <flux:modal.close>
                                                    <flux:button variant="ghost">Cancel</flux:button>
                                                </flux:modal.close>
                                                <flux:button variant="danger"
                                                    wire:click="delete({{ $row->id }})"
                                                    x-on:click="$flux.modal('authorization-master-user-delete-{{ $row->id }}').close()">
                                                    Delete
                                                </flux:button>
                                            </div>
                                        </div>
                                    </flux:modal>
                                @endif
                            @endcan
                        </div>
                    </flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="9" class="text-center text-zinc-500 py-12">
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
    <livewire:authorization-master.person-quick-add />
    <livewire:authorization-master.user-edit-form />
    @endcan
</div>
