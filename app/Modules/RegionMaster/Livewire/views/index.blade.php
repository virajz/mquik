<div>
    {{-- Page heading + primary action + actions menu --}}
    <div class="mb-6 flex items-start justify-between gap-4">
        <div>
            <flux:heading size="xl" level="1">Regions</flux:heading>
            <flux:text class="mt-1">Geographic hierarchy used by Customer, Vendor, and Employee addresses.</flux:text>
        </div>

        <div class="flex items-center gap-2">
            @can('region_master.create')
                <flux:button variant="primary" icon="plus" wire:click="openCreate">
                New Region
            </flux:button>
            @endcan
            <flux:dropdown align="end">
                <flux:button variant="ghost" icon="ellipsis-vertical" />

                <flux:menu>
                    @can('region_master.import')
                    <flux:menu.item icon="arrow-up-tray" wire:click="$dispatch('start-import', { module: 'RegionMaster' })">
                        Import…
                    </flux:menu.item>
                    @endcan
                    @can('region_master.export')
                    <flux:menu.item icon="arrow-down-tray" wire:click="$dispatch('start-export', { module: 'RegionMaster' })">
                        Export
                    </flux:menu.item>
                    @endcan
                </flux:menu>
            </flux:dropdown>
        </div>
    </div>

    {{-- Engines --}}
    <livewire:import-export.export-button :module="'RegionMaster'" wire:key="export-regions" />
    <livewire:import-export.import-wizard :module="'RegionMaster'" wire:key="import-regions" />

    {{-- Filter bar --}}
    <div class="mb-4 flex flex-wrap items-center gap-3">
        <flux:input
            wire:model.live.debounce.300ms="search"
            placeholder="Search by name or code..."
            icon="magnifying-glass"
            clearable
            class="max-w-sm"
        />

        <flux:select wire:model.live="kindFilter" variant="listbox" class="max-w-40">
            <flux:select.option value="all">All kinds</flux:select.option>
            <flux:select.option value="state">State</flux:select.option>
            <flux:select.option value="city">City</flux:select.option>
            <flux:select.option value="area">Area</flux:select.option>
            <flux:select.option value="pincode">Pincode</flux:select.option>
        </flux:select>

        @if ($this->parentFilterOptions->isNotEmpty())
            <flux:select wire:model.live="parentFilter" variant="listbox" searchable class="max-w-56">
                <flux:select.option value="all">All parents</flux:select.option>
                @foreach ($this->parentFilterOptions as $p)
                    <flux:select.option :value="(string) $p->id">{{ $p->name }}</flux:select.option>
                @endforeach
            </flux:select>
        @endif

        <flux:select wire:model.live="statusFilter" variant="listbox" class="max-w-40">
            <flux:select.option value="all">All statuses</flux:select.option>
            <flux:select.option value="active">Active</flux:select.option>
            <flux:select.option value="inactive">Inactive</flux:select.option>
        </flux:select>
    </div>

    {{-- Table --}}
    <flux:table>
        <flux:table.columns>
            <flux:table.column class="w-20" sortable :sorted="$sortBy === 'id'" :direction="$sortDirection" wire:click="sort('id')">
                ID
            </flux:table.column>
            <flux:table.column class="w-28" sortable :sorted="$sortBy === 'kind'" :direction="$sortDirection" wire:click="sort('kind')">
                Kind
            </flux:table.column>
            <flux:table.column sortable :sorted="$sortBy === 'name'" :direction="$sortDirection" wire:click="sort('name')">
                Name
            </flux:table.column>
            <flux:table.column class="w-32" sortable :sorted="$sortBy === 'code'" :direction="$sortDirection" wire:click="sort('code')">
                Code
            </flux:table.column>
            <flux:table.column>Parent</flux:table.column>
            <flux:table.column class="w-24" sortable :sorted="$sortBy === 'is_active'" :direction="$sortDirection" wire:click="sort('is_active')">
                Status
            </flux:table.column>
            <flux:table.column class="w-32" align="end">Actions</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @forelse ($rows as $row)
                <flux:table.row :key="$row->id">
                    <flux:table.cell class="font-mono text-xs text-zinc-500">
                        #{{ str_pad($row->id, 5, '0', STR_PAD_LEFT) }}
                    </flux:table.cell>

                    <flux:table.cell>
                        @php
                            $kindColor = match ($row->kind) {
                                'state' => 'blue',
                                'city' => 'lime',
                                'area' => 'amber',
                                'pincode' => 'zinc',
                                default => 'zinc',
                            };
                        @endphp
                        <flux:badge :color="$kindColor" size="sm">{{ ucfirst($row->kind) }}</flux:badge>
                    </flux:table.cell>

                    <flux:table.cell class="font-medium">{{ $row->name }}</flux:table.cell>

                    <flux:table.cell class="font-mono text-xs text-zinc-500">
                        {{ $row->code ?? '—' }}
                    </flux:table.cell>

                    <flux:table.cell class="text-zinc-500">
                        {{ $row->parent?->name ?? '—' }}
                    </flux:table.cell>

                    <flux:table.cell>
                        @if ($row->is_active)
                            <flux:badge color="lime" size="sm">Active</flux:badge>
                        @else
                            <flux:badge color="zinc" size="sm">Inactive</flux:badge>
                        @endif
                    </flux:table.cell>

                    <flux:table.cell>
                        <div class="flex items-center justify-end gap-1">
                            @can('region_master.update')
                                <flux:button size="sm" variant="ghost" icon="pencil-square"
                                wire:click="openEdit({{ $row->id }})">Edit</flux:button>
                            @endcan
                            @can('region_master.delete')
                                <flux:modal.trigger :name="'region-master-delete-' . $row->id">
                                    <flux:button size="sm" variant="ghost" icon="trash" />
                                </flux:modal.trigger>
                                <flux:modal :name="'region-master-delete-' . $row->id">
                                    <div class="space-y-4">
                                        <flux:heading size="lg">Delete {{ $row->name }}?</flux:heading>
                                        <flux:text>Cannot be undone. Children of this region will become orphaned (parent set to null).</flux:text>
                                        <div class="flex gap-2 justify-end">
                                            <flux:modal.close>
                                                <flux:button variant="ghost">Cancel</flux:button>
                                            </flux:modal.close>
                                            <flux:button variant="danger"
                                                wire:click="delete({{ $row->id }})"
                                                x-on:click="$flux.modal('region-master-delete-{{ $row->id }}').close()">
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
                    <flux:table.cell colspan="7" class="text-center text-zinc-500 py-12">
                        <flux:icon.map-pin class="mx-auto mb-3 size-8 text-zinc-400" />
                        <div class="font-medium">No regions yet</div>
                        <flux:text class="mt-1">Add states, then cities, then areas. Pincodes can be linked to areas.</flux:text>
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

    <livewire:region-master.form />
</div>
