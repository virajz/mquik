<div>
    <div class="mb-6 flex items-start justify-between gap-4">
        <div>
            <flux:heading size="xl" level="1">Inspection Templates</flux:heading>
            <flux:text class="mt-1">Reusable inspection checklists for PMS, Tyre, Bodyshop, Basic, and Custom inspections.</flux:text>
        </div>

        <div class="flex items-center gap-2">
            <flux:button variant="primary" icon="plus" wire:click="openCreate">New Template</flux:button>

            <flux:dropdown align="end">
                <flux:button variant="ghost" icon="ellipsis-vertical" />
                <flux:menu>
                    @can('inspection_template_master.import')
                    <flux:menu.item icon="arrow-up-tray" wire:click="$dispatch('start-import', { module: 'InspectionTemplateMaster' })">Import…</flux:menu.item>
                    @endcan
                    @can('inspection_template_master.export')
                    <flux:menu.item icon="arrow-down-tray" wire:click="$dispatch('start-export', { module: 'InspectionTemplateMaster' })">Export</flux:menu.item>
                    @endcan
                </flux:menu>
            </flux:dropdown>
        </div>
    </div>

    <livewire:import-export.export-button :module="'InspectionTemplateMaster'" wire:key="export-inspection-templates" />
    <livewire:import-export.import-wizard :module="'InspectionTemplateMaster'" wire:key="import-inspection-templates" />

    <div class="mb-4 flex items-center gap-3 flex-wrap">
        <flux:input
            wire:model.live.debounce.300ms="search"
            placeholder="Search by name or code..."
            icon="magnifying-glass"
            clearable
            class="max-w-sm"
        />

        <flux:select wire:model.live="appliesToFilter" variant="listbox" class="max-w-44">
            <flux:select.option value="all">All</flux:select.option>
            @foreach ($appliesToOptions as $opt)
                <flux:select.option :value="$opt">{{ ucfirst($opt) }}</flux:select.option>
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
            <flux:table.column class="w-32" sortable :sorted="$sortBy === 'applies_to'" :direction="$sortDirection" wire:click="sort('applies_to')">Applies To</flux:table.column>
            <flux:table.column class="w-24" align="end">Items</flux:table.column>
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
                        @php
                            $colorMap = [
                                'pms' => 'blue',
                                'tyre' => 'amber',
                                'bodyshop' => 'rose',
                                'basic' => 'lime',
                                'custom' => 'zinc',
                            ];
                            $color = $colorMap[$row->applies_to] ?? 'zinc';
                        @endphp
                        <flux:badge :color="$color" size="sm">{{ ucfirst($row->applies_to) }}</flux:badge>
                    </flux:table.cell>
                    <flux:table.cell class="text-right font-mono text-xs">{{ $row->items_count }}</flux:table.cell>
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
                            <flux:modal.trigger :name="'inspection-template-master-delete-' . $row->id">
                                <flux:button size="sm" variant="ghost" icon="trash" />
                            </flux:modal.trigger>
                            <flux:modal :name="'inspection-template-master-delete-' . $row->id">
                                <div class="space-y-4">
                                    <flux:heading size="lg">Delete {{ $row->name }}?</flux:heading>
                                    <flux:text>Cannot be undone. The pivot links to inspection items will be removed too.</flux:text>
                                    <div class="flex gap-2 justify-end">
                                        <flux:modal.close><flux:button variant="ghost">Cancel</flux:button></flux:modal.close>
                                        <flux:button variant="danger" wire:click="delete({{ $row->id }})" x-on:click="$flux.modal('inspection-template-master-delete-{{ $row->id }}').close()">Delete</flux:button>
                                    </div>
                                </div>
                            </flux:modal>
                        </div>
                    </flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="7" class="text-center text-zinc-500 py-12">
                        <flux:icon.clipboard-document-list class="mx-auto mb-3 size-8 text-zinc-400" />
                        <div class="font-medium">No templates yet</div>
                        <flux:text class="mt-1">Add templates like PMS Standard, Tyre Service, Bodyshop Estimate, Pre-Delivery.</flux:text>
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

    <livewire:inspection-template-master.form />
</div>
