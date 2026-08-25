@php
    $eventColors = [
        'created' => 'lime',
        'updated' => 'amber',
        'deleted' => 'red',
        'restored' => 'zinc',
    ];
@endphp

<div>
    {{-- Page heading --}}
    <div class="mb-6 flex items-start justify-between gap-4">
        <div>
            <flux:heading size="xl" level="1">Audit Log</flux:heading>
            <flux:text class="mt-1">Activity history — who created, modified, or deleted records and when.</flux:text>
        </div>

        <div class="flex items-center gap-2">
            <flux:button variant="ghost" icon="x-mark" wire:click="clearFilters">
                Clear filters
            </flux:button>
        </div>
    </div>

    {{-- Filter bar --}}
    <div class="mb-4 flex flex-wrap items-center gap-3">
        <flux:input
            wire:model.live.debounce.300ms="search"
            placeholder="Search by user or record..."
            icon="magnifying-glass"
            clearable
            class="max-w-sm"
        />

        <flux:select wire:model.live="eventFilter" variant="listbox" placeholder="Event" class="max-w-40">
            <flux:select.option value="all">All events</flux:select.option>
            <flux:select.option value="created">Created</flux:select.option>
            <flux:select.option value="updated">Updated</flux:select.option>
            <flux:select.option value="deleted">Deleted</flux:select.option>
            <flux:select.option value="restored">Restored</flux:select.option>
        </flux:select>

        <flux:select wire:model.live="userFilter" variant="listbox" placeholder="User" class="max-w-48">
            <flux:select.option value="all">All users</flux:select.option>
            @foreach ($userOptions as $u)
                <flux:select.option :value="(string) $u->id">{{ $u->name }}</flux:select.option>
            @endforeach
        </flux:select>

        <flux:select wire:model.live="modelTypeFilter" variant="listbox" placeholder="Module" class="max-w-56">
            <flux:select.option value="all">All modules</flux:select.option>
            @foreach ($modelTypes as $type)
                <flux:select.option :value="$type">
                    {{ \Illuminate\Support\Str::headline(class_basename($type)) }}
                </flux:select.option>
            @endforeach
        </flux:select>

        <flux:date-picker locale="en-IN"
            wire:model.live="dateFrom"
            placeholder="From"
            type="input"
            with-today
            selectable-header
            fixed-weeks
            class="max-w-44"
        />

        <flux:date-picker locale="en-IN"
            wire:model.live="dateTo"
            placeholder="To"
            type="input"
            with-today
            selectable-header
            fixed-weeks
            class="max-w-44"
        />
    </div>

    {{-- Table --}}
    <flux:table>
        <flux:table.columns>
            <flux:table.column class="w-20" sortable :sorted="$sortBy === 'id'" :direction="$sortDirection" wire:click="sort('id')">
                ID
            </flux:table.column>
            <flux:table.column class="w-48" sortable :sorted="$sortBy === 'created_at'" :direction="$sortDirection" wire:click="sort('created_at')">
                When
            </flux:table.column>
            <flux:table.column class="w-48">User</flux:table.column>
            <flux:table.column class="w-28" sortable :sorted="$sortBy === 'event'" :direction="$sortDirection" wire:click="sort('event')">
                Event
            </flux:table.column>
            <flux:table.column class="w-56" sortable :sorted="$sortBy === 'model_type'" :direction="$sortDirection" wire:click="sort('model_type')">
                Module
            </flux:table.column>
            <flux:table.column>Record</flux:table.column>
            <flux:table.column class="w-24" align="end">Actions</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @forelse ($rows as $row)
                <flux:table.row :key="$row->id">
                    <flux:table.cell class="font-mono text-xs text-zinc-500">
                        #{{ str_pad($row->id, 6, '0', STR_PAD_LEFT) }}
                    </flux:table.cell>

                    <flux:table.cell>
                        <flux:tooltip :content="$row->created_at->format('Y-m-d H:i:s')">
                            <span class="text-zinc-700 dark:text-zinc-200">
                                {{ $row->created_at->diffForHumans() }}
                            </span>
                        </flux:tooltip>
                    </flux:table.cell>

                    <flux:table.cell>
                        @if ($row->user)
                            <div class="flex flex-col">
                                <span class="font-medium">{{ $row->user->name }}</span>
                                <span class="text-xs text-zinc-500">{{ $row->user->email }}</span>
                            </div>
                        @elseif ($row->user_name)
                            <div class="flex flex-col">
                                <span class="font-medium">{{ $row->user_name }}</span>
                                <span class="text-xs text-zinc-500">deleted user</span>
                            </div>
                        @else
                            <span class="text-zinc-400 text-xs">system</span>
                        @endif
                    </flux:table.cell>

                    <flux:table.cell>
                        <flux:badge :color="$eventColors[$row->event] ?? 'zinc'" size="sm">
                            {{ ucfirst($row->event) }}
                        </flux:badge>
                    </flux:table.cell>

                    <flux:table.cell class="text-zinc-700 dark:text-zinc-300">
                        {{ \Illuminate\Support\Str::headline(class_basename($row->model_type)) }}
                    </flux:table.cell>

                    <flux:table.cell>
                        <div class="flex flex-col">
                            <span class="font-medium truncate max-w-xs">{{ $row->model_label ?? '—' }}</span>
                            <span class="font-mono text-xs text-zinc-500">#{{ $row->model_id }}</span>
                        </div>
                    </flux:table.cell>

                    <flux:table.cell>
                        <div class="flex items-center justify-end gap-1">
                            <flux:modal.trigger :name="'audit-log-detail-' . $row->id">
                                <flux:button size="sm" variant="ghost" icon="eye">View</flux:button>
                            </flux:modal.trigger>

                            @include('audit-log-master::detail-modal', ['log' => $row, 'eventColors' => $eventColors])
                        </div>
                    </flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="7" class="text-center text-zinc-500 py-12">
                        <flux:icon.clock class="mx-auto mb-3 size-8 text-zinc-400" />
                        <div class="font-medium">No audit entries match these filters</div>
                        <flux:text class="mt-1">Try clearing filters, or wait until activity happens.</flux:text>
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
</div>
