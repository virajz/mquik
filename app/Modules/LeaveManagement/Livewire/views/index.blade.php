<?php
?>
<div>
    <div class="mb-6 flex items-start justify-between gap-4">
        <div>
            <flux:heading size="xl" level="1">Leave Management</flux:heading>
            <flux:text class="mt-1">Leave requests with approval status — types from the Leave Type master.</flux:text>
        </div>
        <div class="flex items-center gap-2">
            @can('leave_management.create')
                <flux:button variant="primary" icon="plus" wire:click="openCreate">New Leave Request</flux:button>
            @endcan
            <flux:dropdown align="end">
                <flux:button variant="ghost" icon="ellipsis-vertical" />
                <flux:menu>
                    <flux:menu.item icon="arrow-up-tray" wire:click="$dispatch('start-import', { module: 'LeaveManagement' })">Import…</flux:menu.item>
                    <flux:menu.item icon="arrow-down-tray" wire:click="$dispatch('start-export', { module: 'LeaveManagement' })">Export</flux:menu.item>
                </flux:menu>
            </flux:dropdown>
        </div>
    </div>

    <livewire:import-export.export-button :module="'LeaveManagement'" wire:key="export-leave" />
    <livewire:import-export.import-wizard :module="'LeaveManagement'" wire:key="import-leave" />

    <div class="mb-4 flex items-center gap-3 flex-wrap">
        <flux:input wire:model.live.debounce.300ms="search" placeholder="Search by employee or reason…" icon="magnifying-glass" clearable class="max-w-sm" />
        <flux:select wire:model.live="employeeFilter" variant="listbox" searchable class="max-w-52">
            <flux:select.option value="all">All employees</flux:select.option>
            @foreach ($this->employees as $e)
                <flux:select.option :value="(string) $e->id">{{ $e->name }}</flux:select.option>
            @endforeach
        </flux:select>
        <flux:select wire:model.live="statusFilter" variant="listbox" class="max-w-36">
            <flux:select.option value="all">All status</flux:select.option>
            @foreach ($statuses as $k => $v)
                <flux:select.option :value="$k">{{ $v }}</flux:select.option>
            @endforeach
        </flux:select>
        <flux:select wire:model.live="typeFilter" variant="listbox" class="max-w-40">
            <flux:select.option value="all">All types</flux:select.option>
            @foreach ($leaveTypes as $k => $v)
                <flux:select.option :value="$k">{{ $v }}</flux:select.option>
            @endforeach
        </flux:select>
        @if ($search || $employeeFilter !== 'all' || $statusFilter !== 'all' || $typeFilter !== 'all')
            <flux:button variant="ghost" size="sm" icon="x-mark" wire:click="clearFilters">Clear</flux:button>
        @endif
    </div>

    <flux:table>
        <flux:table.columns>
            <flux:table.column class="w-20" sortable :sorted="$sortBy === 'id'" :direction="$sortDirection" wire:click="sort('id')">ID</flux:table.column>
            <flux:table.column>Employee</flux:table.column>
            <flux:table.column class="w-24">Type</flux:table.column>
            <flux:table.column class="w-36" sortable :sorted="$sortBy === 'from_date'" :direction="$sortDirection" wire:click="sort('from_date')">From</flux:table.column>
            <flux:table.column class="w-36" sortable :sorted="$sortBy === 'to_date'" :direction="$sortDirection" wire:click="sort('to_date')">To</flux:table.column>
            <flux:table.column class="w-20 text-center">Days</flux:table.column>
            <flux:table.column class="w-32" sortable :sorted="$sortBy === 'status'" :direction="$sortDirection" wire:click="sort('status')">Status</flux:table.column>
            <flux:table.column class="w-32" align="end">Actions</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @forelse ($rows as $row)
                <flux:table.row :key="$row->id">
                    <flux:table.cell class="font-mono text-xs text-zinc-500">#{{ str_pad($row->id, 5, '0', STR_PAD_LEFT) }}</flux:table.cell>
                    <flux:table.cell class="font-medium">{{ $row->employee?->name ?? '—' }}</flux:table.cell>
                    <flux:table.cell><flux:badge color="zinc" size="sm">{{ $row->leave_type }}</flux:badge></flux:table.cell>
                    <flux:table.cell class="text-sm">{{ $row->from_date?->format('d/m/Y') }}</flux:table.cell>
                    <flux:table.cell class="text-sm">{{ $row->to_date?->format('d/m/Y') }}</flux:table.cell>
                    <flux:table.cell class="text-center font-mono text-sm">{{ rtrim(rtrim(number_format($row->days_count, 1), '0'), '.') }}</flux:table.cell>
                    <flux:table.cell>
                        @php($statusColor = match ($row->status) { 'pending' => 'amber', 'approved' => 'lime', 'rejected' => 'red', 'cancelled' => 'zinc', default => 'zinc' })
                        <flux:badge :color="$statusColor" size="sm">{{ $statuses[$row->status] ?? $row->status }}</flux:badge>
                    </flux:table.cell>
                    <flux:table.cell>
                        <div class="flex items-center justify-end gap-1">
                            @can('leave_management.update')
                                <flux:button size="sm" variant="ghost" icon="pencil-square" wire:click="openEdit({{ $row->id }})">Edit</flux:button>
                            @endcan
                            @can('leave_management.delete')
                                <flux:modal.trigger :name="'leave-delete-' . $row->id">
                                    <flux:button size="sm" variant="ghost" icon="trash" />
                                </flux:modal.trigger>
                                <flux:modal :name="'leave-delete-' . $row->id">
                                    <div class="space-y-4">
                                        <flux:heading size="lg">Delete leave request #{{ str_pad($row->id, 5, '0', STR_PAD_LEFT) }}?</flux:heading>
                                        <flux:text>Cannot be undone.</flux:text>
                                        <div class="flex gap-2 justify-end">
                                            <flux:modal.close><flux:button variant="ghost">Cancel</flux:button></flux:modal.close>
                                            <flux:button variant="danger" wire:click="delete({{ $row->id }})" x-on:click="$flux.modal('leave-delete-{{ $row->id }}').close()">Delete</flux:button>
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
                        <flux:icon.calendar-days class="mx-auto mb-3 size-8 text-zinc-400" />
                        <div class="font-medium">No leave requests yet</div>
                        <flux:text class="mt-1">Add one to start the approval flow.</flux:text>
                    </flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>

    @if ($rows->hasPages())
        <div class="mt-4"><flux:pagination :paginator="$rows" /></div>
    @endif

    <livewire:leave-management.form />
</div>
