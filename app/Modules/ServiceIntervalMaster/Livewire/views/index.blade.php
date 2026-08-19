<div>
    <div class="mb-6 flex flex-wrap items-start justify-between gap-4">
        <div>
            <flux:heading size="xl" level="1">Service Intervals</flux:heading>
            <flux:text size="sm" class="mt-1 text-zinc-500">
                How often each service falls due. Whichever comes first — months or kilometres — marks it due on a vehicle's history.
            </flux:text>
        </div>
        <div class="flex shrink-0 items-center gap-2">
            @can('service_interval_master.create')
                <flux:button variant="primary" icon="plus" wire:click="openCreate">
                    New Service Interval
                </flux:button>
            @endcan
            <flux:dropdown align="end">
                <flux:button variant="ghost" icon="ellipsis-vertical" />
                <flux:menu>
                    <flux:menu.item icon="arrow-up-tray" wire:click="$dispatch('start-import', { module: 'ServiceIntervalMaster' })">Import…</flux:menu.item>
                    <flux:menu.item icon="arrow-down-tray" wire:click="$dispatch('start-export', { module: 'ServiceIntervalMaster' })">Export</flux:menu.item>
                </flux:menu>
            </flux:dropdown>
        </div>
    </div>

    <div class="mb-4 flex flex-wrap items-end gap-3">
        <flux:input wire:model.live.debounce.300ms="search" icon="magnifying-glass" clearable
            placeholder="Search a service…" class="w-full sm:w-80" />
        <flux:select wire:model.live="statusFilter" variant="listbox" class="w-full sm:w-44">
            <flux:select.option value="all">All</flux:select.option>
            <flux:select.option value="active">Active</flux:select.option>
            <flux:select.option value="inactive">Inactive</flux:select.option>
        </flux:select>
    </div>

    <flux:table>
        <flux:table.columns>
            <flux:table.column sortable :sorted="$sortBy === 'id'" :direction="$sortDirection" wire:click="sort('id')">ID</flux:table.column>
            <flux:table.column sortable :sorted="$sortBy === 'name'" :direction="$sortDirection" wire:click="sort('name')">Service</flux:table.column>
            <flux:table.column sortable :sorted="$sortBy === 'interval_months'" :direction="$sortDirection" wire:click="sort('interval_months')" align="end">Every (months)</flux:table.column>
            <flux:table.column sortable :sorted="$sortBy === 'interval_km'" :direction="$sortDirection" wire:click="sort('interval_km')" align="end">Every (km)</flux:table.column>
            <flux:table.column>Status</flux:table.column>
            <flux:table.column class="w-32" align="end">Actions</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @forelse ($rows as $row)
                <flux:table.row wire:key="si-{{ $row->id }}">
                    <flux:table.cell class="font-mono text-xs text-zinc-500">#{{ str_pad($row->id, 5, '0', STR_PAD_LEFT) }}</flux:table.cell>
                    <flux:table.cell>
                        <div class="font-medium">{{ $row->name }}</div>
                        @if ($row->description)
                            <div class="text-xs text-zinc-500">{{ \Illuminate\Support\Str::limit($row->description, 60) }}</div>
                        @endif
                    </flux:table.cell>
                    <flux:table.cell align="end" class="font-mono">{{ $row->interval_months ?? '—' }}</flux:table.cell>
                    <flux:table.cell align="end" class="font-mono">{{ $row->interval_km ? number_format($row->interval_km) : '—' }}</flux:table.cell>
                    <flux:table.cell>
                        @if (! $row->hasBound())
                            <flux:badge size="sm" color="zinc">Never due</flux:badge>
                        @else
                            <flux:badge size="sm" :color="$row->is_active ? 'lime' : 'zinc'">{{ $row->is_active ? 'Active' : 'Inactive' }}</flux:badge>
                        @endif
                    </flux:table.cell>
                    <flux:table.cell align="end">
                        <div class="flex items-center justify-end gap-1">
                            @can('service_interval_master.update')
                                <flux:button size="sm" variant="ghost" icon="pencil-square"
                                    wire:click="openEdit({{ $row->id }})">Edit</flux:button>
                            @endcan
                            @can('service_interval_master.delete')
                                <flux:modal.trigger :name="'service-interval-master-delete-' . $row->id">
                                    <flux:button size="sm" variant="ghost" icon="trash" />
                                </flux:modal.trigger>
                                <flux:modal :name="'service-interval-master-delete-' . $row->id">
                                    <div class="space-y-4">
                                        <flux:heading size="lg">Delete {{ $row->name }}?</flux:heading>
                                        <flux:text>This service will no longer be flagged as due on any vehicle's history. Past visits are untouched.</flux:text>
                                        <div class="flex gap-2 justify-end">
                                            <flux:modal.close>
                                                <flux:button variant="ghost">Cancel</flux:button>
                                            </flux:modal.close>
                                            <flux:button variant="danger"
                                                wire:click="delete({{ $row->id }})"
                                                x-on:click="$flux.modal('service-interval-master-delete-{{ $row->id }}').close()">
                                                Delete
                                            </flux:button>
                                        </div>
                                    </div>
                                </flux:modal>
                            @endcan
                        </div>
                    </flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="6">
                        <div class="py-12 text-center">
                            <flux:icon.clock class="mx-auto size-8 text-zinc-300 dark:text-zinc-600" />
                            <flux:heading size="lg" class="mt-3">No service intervals</flux:heading>
                            <flux:text size="sm" class="mt-1 text-zinc-500">Add one so the service history can tell you what's due.</flux:text>
                        </div>
                    </flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>

    <flux:pagination :paginator="$rows" class="mt-4" />

    <livewire:service-interval-master.form />
    <livewire:import-export.export-button :module="'ServiceIntervalMaster'" wire:key="export-service-intervals" />
    <livewire:import-export.import-wizard :module="'ServiceIntervalMaster'" wire:key="import-service-intervals" />
</div>
