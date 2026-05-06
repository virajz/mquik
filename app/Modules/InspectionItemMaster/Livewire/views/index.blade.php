<div>
    <div class="mb-6 flex items-start justify-between gap-4">
        <div>
            <flux:heading size="xl" level="1">Inspection Items</flux:heading>
            <flux:text class="mt-1">Individual checkpoints performed during inspections — Engine Oil Level, Tyre Tread Depth, Brake Pad Thickness, etc.</flux:text>
        </div>

        <div class="flex items-center gap-2">
            <flux:button variant="primary" icon="plus" wire:click="openCreate">New Item</flux:button>

            <flux:dropdown align="end">
                <flux:button variant="ghost" icon="ellipsis-vertical" />
                <flux:menu>
                    <flux:menu.item icon="arrow-up-tray" wire:click="$dispatch('start-import', { module: 'InspectionItemMaster' })">Import…</flux:menu.item>
                    <flux:menu.item icon="arrow-down-tray" wire:click="$dispatch('start-export', { module: 'InspectionItemMaster' })">Export</flux:menu.item>
                </flux:menu>
            </flux:dropdown>
        </div>
    </div>

    <livewire:import-export.export-button :module="'InspectionItemMaster'" wire:key="export-inspection-items" />
    <livewire:import-export.import-wizard :module="'InspectionItemMaster'" wire:key="import-inspection-items" />

    <div class="mb-4 flex items-center gap-3 flex-wrap">
        <flux:input
            wire:model.live.debounce.300ms="search"
            placeholder="Search by name or code..."
            icon="magnifying-glass"
            clearable
            class="max-w-sm"
        />

        <flux:select wire:model.live="groupFilter" variant="listbox" searchable class="max-w-52">
            <flux:select.option value="all">All groups</flux:select.option>
            @foreach ($groups as $g)
                <flux:select.option :value="(string) $g->id">{{ $g->name }}</flux:select.option>
            @endforeach
        </flux:select>

        <flux:select wire:model.live="checkTypeFilter" variant="listbox" class="max-w-44">
            <flux:select.option value="all">All check types</flux:select.option>
            @foreach ($checkTypes as $ct)
                <flux:select.option :value="$ct">{{ str_replace('_', '/', ucfirst($ct)) }}</flux:select.option>
            @endforeach
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
            <flux:table.column sortable :sorted="$sortBy === 'name'" :direction="$sortDirection" wire:click="sort('name')">Name</flux:table.column>
            <flux:table.column class="w-32" sortable :sorted="$sortBy === 'code'" :direction="$sortDirection" wire:click="sort('code')">Code</flux:table.column>
            <flux:table.column class="w-44" sortable :sorted="$sortBy === 'inspection_item_group_id'" :direction="$sortDirection" wire:click="sort('inspection_item_group_id')">Group</flux:table.column>
            <flux:table.column class="w-36" sortable :sorted="$sortBy === 'check_type'" :direction="$sortDirection" wire:click="sort('check_type')">Check Type</flux:table.column>
            <flux:table.column class="w-24">Unit</flux:table.column>
            <flux:table.column class="w-24" sortable :sorted="$sortBy === 'is_active'" :direction="$sortDirection" wire:click="sort('is_active')">Status</flux:table.column>
            <flux:table.column class="w-32" align="end">Actions</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @forelse ($rows as $row)
                <flux:table.row :key="$row->id">
                    <flux:table.cell class="font-mono text-xs text-zinc-500">#{{ str_pad($row->id, 5, '0', STR_PAD_LEFT) }}</flux:table.cell>
                    <flux:table.cell class="font-medium">{{ $row->name }}</flux:table.cell>
                    <flux:table.cell class="font-mono text-xs text-zinc-500">{{ $row->code ?? '—' }}</flux:table.cell>
                    <flux:table.cell>
                        @if ($row->group)
                            <flux:badge color="sky" size="sm">{{ $row->group->name }}</flux:badge>
                        @else
                            <span class="text-zinc-400">—</span>
                        @endif
                    </flux:table.cell>
                    <flux:table.cell>
                        @php
                            $colorMap = [
                                'visual' => 'zinc',
                                'measurement' => 'blue',
                                'yes_no' => 'amber',
                                'rating' => 'violet',
                            ];
                            $color = $colorMap[$row->check_type] ?? 'zinc';
                        @endphp
                        <flux:badge :color="$color" size="sm">{{ str_replace('_', '/', ucfirst($row->check_type)) }}</flux:badge>
                    </flux:table.cell>
                    <flux:table.cell class="font-mono text-xs text-zinc-500">{{ $row->measurement_unit ?? '—' }}</flux:table.cell>
                    <flux:table.cell>
                        @if ($row->is_active)
                            <flux:badge color="lime" size="sm">Active</flux:badge>
                        @else
                            <flux:badge color="zinc" size="sm">Inactive</flux:badge>
                        @endif
                    </flux:table.cell>
                    <flux:table.cell>
                        <div class="flex items-center justify-end gap-1">
                            <flux:button size="sm" variant="ghost" icon="pencil-square" wire:click="openEdit({{ $row->id }})">Edit</flux:button>
                            <flux:modal.trigger :name="'inspection-item-master-delete-' . $row->id">
                                <flux:button size="sm" variant="ghost" icon="trash" />
                            </flux:modal.trigger>
                            <flux:modal :name="'inspection-item-master-delete-' . $row->id">
                                <div class="space-y-4">
                                    <flux:heading size="lg">Delete {{ $row->name }}?</flux:heading>
                                    <flux:text>Cannot be undone. If this item is used by any inspection template, the delete will fail.</flux:text>
                                    <div class="flex gap-2 justify-end">
                                        <flux:modal.close><flux:button variant="ghost">Cancel</flux:button></flux:modal.close>
                                        <flux:button variant="danger" wire:click="delete({{ $row->id }})" x-on:click="$flux.modal('inspection-item-master-delete-{{ $row->id }}').close()">Delete</flux:button>
                                    </div>
                                </div>
                            </flux:modal>
                        </div>
                    </flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="8" class="text-center text-zinc-500 py-12">
                        <flux:icon.magnifying-glass-circle class="mx-auto mb-3 size-8 text-zinc-400" />
                        <div class="font-medium">No inspection items yet</div>
                        <flux:text class="mt-1">Add items like Engine Oil Level, Brake Pad Thickness, Tyre Tread Depth.</flux:text>
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

    <livewire:inspection-item-master.form />
</div>
