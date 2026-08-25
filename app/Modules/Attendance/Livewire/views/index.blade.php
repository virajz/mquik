<?php
?>
<div>
    <div class="mb-6 flex items-start justify-between gap-4">
        <div>
            <flux:heading size="xl" level="1">Attendance</flux:heading>
            <flux:text class="mt-1">Daily punch log — in/out per employee, optional selfie + location.</flux:text>
        </div>
        <div class="flex items-center gap-2">
            @can('attendance.create')
                <flux:button variant="primary" icon="plus" wire:click="openCreate">New Punch</flux:button>
            @endcan
            <flux:dropdown align="end">
                <flux:button variant="ghost" icon="ellipsis-vertical" />
                <flux:menu>
                    <flux:menu.item icon="arrow-up-tray" wire:click="$dispatch('start-import', { module: 'Attendance' })">Import…</flux:menu.item>
                    <flux:menu.item icon="arrow-down-tray" wire:click="$dispatch('start-export', { module: 'Attendance' })">Export</flux:menu.item>
                </flux:menu>
            </flux:dropdown>
        </div>
    </div>

    <livewire:import-export.export-button :module="'Attendance'" wire:key="export-attendance" />
    <livewire:import-export.import-wizard :module="'Attendance'" wire:key="import-attendance" />

    <div class="mb-4 flex items-center gap-3 flex-wrap">
        <flux:input wire:model.live.debounce.300ms="search" placeholder="Search by employee or notes…" icon="magnifying-glass" clearable class="max-w-sm" />
        <flux:select wire:model.live="employeeFilter" variant="listbox" searchable class="max-w-52">
            <flux:select.option value="all">All employees</flux:select.option>
            @foreach ($this->employees as $e)
                <flux:select.option :value="(string) $e->id">{{ $e->name }}</flux:select.option>
            @endforeach
        </flux:select>
        <flux:select wire:model.live="typeFilter" variant="listbox" class="max-w-36">
            <flux:select.option value="all">All types</flux:select.option>
            @foreach ($types as $key => $label)
                <flux:select.option :value="$key">{{ $label }}</flux:select.option>
            @endforeach
        </flux:select>
        <flux:date-picker locale="en-IN" wire:model.live="dateFrom" placeholder="From" with-today selectable-header fixed-weeks type="input" clearable class="max-w-40" />
        <flux:date-picker locale="en-IN" wire:model.live="dateTo" placeholder="To" with-today selectable-header fixed-weeks type="input" clearable class="max-w-40" />
        @if ($search || $employeeFilter !== 'all' || $typeFilter !== 'all' || $dateFrom || $dateTo)
            <flux:button variant="ghost" size="sm" icon="x-mark" wire:click="clearFilters">Clear</flux:button>
        @endif
    </div>

    <flux:table>
        <flux:table.columns>
            <flux:table.column class="w-20" sortable :sorted="$sortBy === 'id'" :direction="$sortDirection" wire:click="sort('id')">ID</flux:table.column>
            <flux:table.column class="w-40" sortable :sorted="$sortBy === 'punched_at'" :direction="$sortDirection" wire:click="sort('punched_at')">Punched</flux:table.column>
            <flux:table.column>Employee</flux:table.column>
            <flux:table.column class="w-24" sortable :sorted="$sortBy === 'type'" :direction="$sortDirection" wire:click="sort('type')">Type</flux:table.column>
            <flux:table.column class="w-16 text-center">Selfie</flux:table.column>
            <flux:table.column>Notes</flux:table.column>
            <flux:table.column class="w-32" align="end">Actions</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @forelse ($rows as $row)
                <flux:table.row :key="$row->id">
                    <flux:table.cell class="font-mono text-xs text-zinc-500">#{{ str_pad($row->id, 5, '0', STR_PAD_LEFT) }}</flux:table.cell>
                    <flux:table.cell class="text-sm">
                        <div class="font-medium">{{ $row->punched_at?->format('d/m/Y') }}</div>
                        <div class="text-xs text-zinc-500 mt-0.5">{{ $row->punched_at?->format('h:i A') }}</div>
                    </flux:table.cell>
                    <flux:table.cell class="font-medium">{{ $row->employee?->name ?? '—' }}</flux:table.cell>
                    <flux:table.cell>
                        <flux:badge :color="$row->type === 'in' ? 'lime' : 'amber'" size="sm">{{ $types[$row->type] ?? $row->type }}</flux:badge>
                    </flux:table.cell>
                    <flux:table.cell class="text-center">
                        @if ($row->selfie_path)
                            <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($row->selfie_path) }}" alt="selfie" class="size-8 rounded-full object-cover mx-auto" />
                        @else
                            <span class="text-zinc-400 text-xs">—</span>
                        @endif
                    </flux:table.cell>
                    <flux:table.cell class="text-zinc-500 max-w-md truncate">{{ $row->notes }}</flux:table.cell>
                    <flux:table.cell>
                        <div class="flex items-center justify-end gap-1">
                            @can('attendance.update')
                                <flux:button size="sm" variant="ghost" icon="pencil-square" wire:click="openEdit({{ $row->id }})">Edit</flux:button>
                            @endcan
                            @can('attendance.delete')
                                <flux:modal.trigger :name="'attendance-delete-' . $row->id">
                                    <flux:button size="sm" variant="ghost" icon="trash" />
                                </flux:modal.trigger>
                                <flux:modal :name="'attendance-delete-' . $row->id">
                                    <div class="space-y-4">
                                        <flux:heading size="lg">Delete punch #{{ str_pad($row->id, 5, '0', STR_PAD_LEFT) }}?</flux:heading>
                                        <flux:text>Cannot be undone.</flux:text>
                                        <div class="flex gap-2 justify-end">
                                            <flux:modal.close><flux:button variant="ghost">Cancel</flux:button></flux:modal.close>
                                            <flux:button variant="danger" wire:click="delete({{ $row->id }})" x-on:click="$flux.modal('attendance-delete-{{ $row->id }}').close()">Delete</flux:button>
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
                        <flux:icon.finger-print class="mx-auto mb-3 size-8 text-zinc-400" />
                        <div class="font-medium">No punches recorded yet</div>
                        <flux:text class="mt-1">Record a new punch to get started.</flux:text>
                    </flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>

    @if ($rows->hasPages())
        <div class="mt-4"><flux:pagination :paginator="$rows" /></div>
    @endif

    <livewire:attendance.form />
</div>
