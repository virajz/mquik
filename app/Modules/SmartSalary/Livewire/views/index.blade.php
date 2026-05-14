<?php
?>
<div>
    <div class="mb-6 flex items-start justify-between gap-4">
        <div>
            <flux:heading size="xl" level="1">Smart Salary — KPIs</flux:heading>
            <flux:text class="mt-1">Registry of performance metrics. Formulas wire up in Week 7.</flux:text>
        </div>
        <div class="flex items-center gap-2">
            @can('smart_salary.create')
                <flux:button variant="primary" icon="plus" wire:click="openCreate">New KPI</flux:button>
            @endcan
            <flux:dropdown align="end">
                <flux:button variant="ghost" icon="ellipsis-vertical" />
                <flux:menu>
                    <flux:menu.item icon="arrow-up-tray" wire:click="$dispatch('start-import', { module: 'SmartSalary' })">Import…</flux:menu.item>
                    <flux:menu.item icon="arrow-down-tray" wire:click="$dispatch('start-export', { module: 'SmartSalary' })">Export</flux:menu.item>
                </flux:menu>
            </flux:dropdown>
        </div>
    </div>

    <livewire:import-export.export-button :module="'SmartSalary'" wire:key="export-smart-salary" />
    <livewire:import-export.import-wizard :module="'SmartSalary'" wire:key="import-smart-salary" />

    <div class="mb-4 flex items-center gap-3 flex-wrap">
        <flux:input wire:model.live.debounce.300ms="search" placeholder="Search by key, name, description…" icon="magnifying-glass" clearable class="max-w-sm" />
        <flux:select wire:model.live="categoryFilter" variant="listbox" class="max-w-44">
            <flux:select.option value="all">All categories</flux:select.option>
            @foreach ($categories as $c)
                <flux:select.option :value="$c">{{ $c }}</flux:select.option>
            @endforeach
        </flux:select>
        <flux:select wire:model.live="activeFilter" variant="listbox" class="max-w-36">
            <flux:select.option value="all">All status</flux:select.option>
            <flux:select.option value="yes">Active</flux:select.option>
            <flux:select.option value="no">Inactive</flux:select.option>
        </flux:select>
        @if ($search || $categoryFilter !== 'all' || $activeFilter !== 'all')
            <flux:button variant="ghost" size="sm" icon="x-mark" wire:click="clearFilters">Clear</flux:button>
        @endif
    </div>

    <flux:table>
        <flux:table.columns>
            <flux:table.column class="w-20" sortable :sorted="$sortBy === 'id'" :direction="$sortDirection" wire:click="sort('id')">ID</flux:table.column>
            <flux:table.column class="w-48" sortable :sorted="$sortBy === 'key'" :direction="$sortDirection" wire:click="sort('key')">Key</flux:table.column>
            <flux:table.column sortable :sorted="$sortBy === 'name'" :direction="$sortDirection" wire:click="sort('name')">Name</flux:table.column>
            <flux:table.column class="w-36" sortable :sorted="$sortBy === 'category'" :direction="$sortDirection" wire:click="sort('category')">Category</flux:table.column>
            <flux:table.column class="w-20 text-right" sortable :sorted="$sortBy === 'weight'" :direction="$sortDirection" wire:click="sort('weight')">Weight</flux:table.column>
            <flux:table.column class="w-20 text-center" sortable :sorted="$sortBy === 'is_active'" :direction="$sortDirection" wire:click="sort('is_active')">Active</flux:table.column>
            <flux:table.column class="w-32" align="end">Actions</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @forelse ($rows as $row)
                <flux:table.row :key="$row->id">
                    <flux:table.cell class="font-mono text-xs text-zinc-500">#{{ str_pad($row->id, 5, '0', STR_PAD_LEFT) }}</flux:table.cell>
                    <flux:table.cell class="font-mono text-xs">{{ $row->key }}</flux:table.cell>
                    <flux:table.cell class="font-medium">{{ $row->name }}</flux:table.cell>
                    <flux:table.cell><flux:badge color="zinc" size="sm">{{ $row->category }}</flux:badge></flux:table.cell>
                    <flux:table.cell class="text-right font-mono text-sm">{{ rtrim(rtrim(number_format($row->weight, 2), '0'), '.') }}</flux:table.cell>
                    <flux:table.cell class="text-center">
                        @if ($row->is_active)
                            <flux:badge color="lime" size="sm">Yes</flux:badge>
                        @else
                            <flux:badge color="zinc" size="sm">No</flux:badge>
                        @endif
                    </flux:table.cell>
                    <flux:table.cell>
                        <div class="flex items-center justify-end gap-1">
                            @can('smart_salary.update')
                                <flux:button size="sm" variant="ghost" icon="pencil-square" wire:click="openEdit({{ $row->id }})">Edit</flux:button>
                            @endcan
                            @can('smart_salary.delete')
                                <flux:modal.trigger :name="'smart-salary-delete-' . $row->id">
                                    <flux:button size="sm" variant="ghost" icon="trash" />
                                </flux:modal.trigger>
                                <flux:modal :name="'smart-salary-delete-' . $row->id">
                                    <div class="space-y-4">
                                        <flux:heading size="lg">Remove KPI {{ $row->key }}?</flux:heading>
                                        <flux:text>Cannot be undone.</flux:text>
                                        <div class="flex gap-2 justify-end">
                                            <flux:modal.close><flux:button variant="ghost">Cancel</flux:button></flux:modal.close>
                                            <flux:button variant="danger" wire:click="delete({{ $row->id }})" x-on:click="$flux.modal('smart-salary-delete-{{ $row->id }}').close()">Delete</flux:button>
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
                        <flux:icon.chart-bar class="mx-auto mb-3 size-8 text-zinc-400" />
                        <div class="font-medium">No KPIs registered yet</div>
                        <flux:text class="mt-1">Add your first KPI — formulas wire up in Week 7.</flux:text>
                    </flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>

    @if ($rows->hasPages())
        <div class="mt-4"><flux:pagination :paginator="$rows" /></div>
    @endif

    <livewire:smart-salary.form />
</div>
