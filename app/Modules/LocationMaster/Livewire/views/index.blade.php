<div>
    <div class="mb-6 flex items-start justify-between gap-4">
        <div>
            <flux:heading size="xl" level="1">Locations</flux:heading>
            <flux:text class="mt-1">Workshop branches — used by Job Cards, Inventory, Invoicing for branch-level reporting.</flux:text>
        </div>
        <div class="flex items-center gap-2">
            @can('location_master.create')
                <flux:button variant="primary" icon="plus" wire:click="openCreate">New Location</flux:button>
            @endcan
            <flux:dropdown align="end">
                <flux:button variant="ghost" icon="ellipsis-vertical" />
                <flux:menu>
                    @can('location_master.import')
                    <flux:menu.item icon="arrow-up-tray" wire:click="$dispatch('start-import', { module: 'LocationMaster' })">Import…</flux:menu.item>
                    @endcan
                    @can('location_master.export')
                    <flux:menu.item icon="arrow-down-tray" wire:click="$dispatch('start-export', { module: 'LocationMaster' })">Export</flux:menu.item>
                    @endcan
                </flux:menu>
            </flux:dropdown>
        </div>
    </div>

    <livewire:import-export.export-button :module="'LocationMaster'" wire:key="export-locations" />
    <livewire:import-export.import-wizard :module="'LocationMaster'" wire:key="import-locations" />

    <div class="mb-4 flex items-center gap-3 flex-wrap">
        <flux:input wire:model.live.debounce.300ms="search" placeholder="Search by name, code, or GSTIN..." icon="magnifying-glass" clearable class="max-w-md" />

        <flux:select wire:model.live="headOfficeFilter" variant="listbox" class="max-w-44">
            <flux:select.option value="all">All locations</flux:select.option>
            <flux:select.option value="head_office">Head office only</flux:select.option>
            <flux:select.option value="branch">Branches only</flux:select.option>
        </flux:select>

        <flux:select wire:model.live="statusFilter" variant="listbox" class="max-w-40">
            <flux:select.option value="all">All</flux:select.option>
            <flux:select.option value="active">Active</flux:select.option>
            <flux:select.option value="inactive">Inactive</flux:select.option>
        </flux:select>
    </div>

    <flux:table>
        <flux:table.columns>
            <flux:table.column class="w-20" sortable :sorted="$sortBy === 'id'" :direction="$sortDirection" wire:click="sort('id')">ID</flux:table.column>
            <flux:table.column class="w-28" sortable :sorted="$sortBy === 'code'" :direction="$sortDirection" wire:click="sort('code')">Code</flux:table.column>
            <flux:table.column sortable :sorted="$sortBy === 'name'" :direction="$sortDirection" wire:click="sort('name')">Name</flux:table.column>
            <flux:table.column class="w-40">City</flux:table.column>
            <flux:table.column class="w-32">State</flux:table.column>
            <flux:table.column class="w-44">GSTIN</flux:table.column>
            <flux:table.column class="w-24" sortable :sorted="$sortBy === 'is_active'" :direction="$sortDirection" wire:click="sort('is_active')">Status</flux:table.column>
            <flux:table.column class="w-32" align="end">Actions</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @forelse ($rows as $row)
                <flux:table.row :key="$row->id">
                    <flux:table.cell class="font-mono text-xs text-zinc-500">#{{ str_pad($row->id, 5, '0', STR_PAD_LEFT) }}</flux:table.cell>
                    <flux:table.cell class="font-mono text-xs">{{ $row->code }}</flux:table.cell>
                    <flux:table.cell>
                        <div class="font-medium flex items-center gap-2">
                            <span>{{ $row->name }}</span>
                            @if ($row->is_head_office)
                                <flux:badge color="blue" size="sm">Head Office</flux:badge>
                            @endif
                        </div>
                    </flux:table.cell>
                    <flux:table.cell class="text-sm">{{ $row->city?->name ?? '—' }}</flux:table.cell>
                    <flux:table.cell class="text-zinc-500 text-sm">{{ $row->state?->name ?? '—' }}</flux:table.cell>
                    <flux:table.cell class="font-mono text-xs text-zinc-500">{{ $row->gstin ?? '—' }}</flux:table.cell>
                    <flux:table.cell>
                        @if ($row->is_active)
                            <flux:badge color="lime" size="sm">Active</flux:badge>
                        @else
                            <flux:badge color="zinc" size="sm">Inactive</flux:badge>
                        @endif
                    </flux:table.cell>
                    <flux:table.cell>
                        <div class="flex items-center justify-end gap-1">
                            @can('location_master.update')
                                <flux:button size="sm" variant="ghost" icon="pencil-square" wire:click="openEdit({{ $row->id }})">Edit</flux:button>
                            @endcan
                            @can('location_master.delete')
                                <flux:modal.trigger :name="'location-master-delete-' . $row->id">
                                    <flux:button size="sm" variant="ghost" icon="trash" />
                                </flux:modal.trigger>
                                <flux:modal :name="'location-master-delete-' . $row->id">
                                    <div class="space-y-4">
                                        <flux:heading size="lg">Delete {{ $row->name }}?</flux:heading>
                                        <flux:text>Cannot be undone. If this location has job cards, inventory, or invoices the delete will fail.</flux:text>
                                        <div class="flex gap-2 justify-end">
                                            <flux:modal.close><flux:button variant="ghost">Cancel</flux:button></flux:modal.close>
                                            <flux:button variant="danger" wire:click="delete({{ $row->id }})" x-on:click="$flux.modal('location-master-delete-{{ $row->id }}').close()">Delete</flux:button>
                                        </div>
                                    </div>
                                </flux:modal>
                            @endcan
                        </div>
                    </flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="8" class="text-center text-zinc-500 py-12">
                        <flux:icon.building-office-2 class="mx-auto mb-3 size-8 text-zinc-400" />
                        <div class="font-medium">No locations yet</div>
                        <flux:text class="mt-1">Add your workshop branches. Each branch has its own GSTIN.</flux:text>
                    </flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>

    @if ($rows->hasPages())<div class="mt-4"><flux:pagination :paginator="$rows" /></div>@endif

    <livewire:location-master.form />
</div>
