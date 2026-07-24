<?php
?>
<div>
    <div class="mb-6 flex items-start justify-between gap-4">
        <div>
            <flux:heading size="xl" level="1">Payroll</flux:heading>
            <flux:text class="mt-1">Manual monthly entry — basic, HRA, DA, allowances, deductions. Salary slip print + auto calc come in Week 7.</flux:text>
        </div>
        <div class="flex items-center gap-2">
            @can('payroll.create')
                <flux:button variant="primary" icon="plus" :href="route('payroll.create')" wire:navigate>New Entry</flux:button>
            @endcan
            <flux:dropdown align="end">
                <flux:button variant="ghost" icon="ellipsis-vertical" />
                <flux:menu>
                    <flux:menu.item icon="arrow-up-tray" wire:click="$dispatch('start-import', { module: 'Payroll' })">Import…</flux:menu.item>
                    <flux:menu.item icon="arrow-down-tray" wire:click="$dispatch('start-export', { module: 'Payroll' })">Export</flux:menu.item>
                </flux:menu>
            </flux:dropdown>
        </div>
    </div>

    <livewire:import-export.export-button :module="'Payroll'" wire:key="export-payroll" />
    <livewire:import-export.import-wizard :module="'Payroll'" wire:key="import-payroll" />

    <div class="mb-4 flex items-center gap-3 flex-wrap">
        <flux:input wire:model.live.debounce.300ms="search" placeholder="Search by employee or notes…" icon="magnifying-glass" clearable class="max-w-sm" />
        <flux:select wire:model.live="employeeFilter" variant="listbox" searchable class="max-w-52">
            <flux:select.option value="all">All employees</flux:select.option>
            @foreach ($this->employees as $e)
                <flux:select.option :value="(string) $e->id">{{ $e->name }}</flux:select.option>
            @endforeach
        </flux:select>
        <flux:select wire:model.live="monthFilter" variant="listbox" class="max-w-36">
            <flux:select.option value="all">All months</flux:select.option>
            @foreach ($months as $n => $name)
                <flux:select.option :value="(string) $n">{{ $name }}</flux:select.option>
            @endforeach
        </flux:select>
        <flux:input wire:model.live="yearFilter" type="number" placeholder="Year" min="2020" max="2100" class:input="font-mono" clearable class="max-w-28" />
        <flux:select wire:model.live="statusFilter" variant="listbox" class="max-w-36">
            <flux:select.option value="all">All status</flux:select.option>
            @foreach ($statuses as $k => $v)
                <flux:select.option :value="$k">{{ $v }}</flux:select.option>
            @endforeach
        </flux:select>
        @if ($search || $employeeFilter !== 'all' || $statusFilter !== 'all' || $yearFilter !== '' || $monthFilter !== 'all')
            <flux:button variant="ghost" size="sm" icon="x-mark" wire:click="clearFilters">Clear</flux:button>
        @endif
    </div>

    <flux:table>
        <flux:table.columns>
            <flux:table.column class="w-20" sortable :sorted="$sortBy === 'id'" :direction="$sortDirection" wire:click="sort('id')">ID</flux:table.column>
            <flux:table.column>Employee</flux:table.column>
            <flux:table.column class="w-36" sortable :sorted="$sortBy === 'period_year'" :direction="$sortDirection" wire:click="sort('period_year')">Period</flux:table.column>
            <flux:table.column class="w-28 text-right" sortable :sorted="$sortBy === 'gross_amount'" :direction="$sortDirection" wire:click="sort('gross_amount')">Gross</flux:table.column>
            <flux:table.column class="w-28 text-right">Deductions</flux:table.column>
            <flux:table.column class="w-28 text-right" sortable :sorted="$sortBy === 'net_amount'" :direction="$sortDirection" wire:click="sort('net_amount')">Net</flux:table.column>
            <flux:table.column class="w-28" sortable :sorted="$sortBy === 'status'" :direction="$sortDirection" wire:click="sort('status')">Status</flux:table.column>
            <flux:table.column class="w-32" align="end">Actions</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @forelse ($rows as $row)
                <flux:table.row :key="$row->id">
                    <flux:table.cell class="font-mono text-xs text-zinc-500">#{{ str_pad($row->id, 5, '0', STR_PAD_LEFT) }}</flux:table.cell>
                    <flux:table.cell class="font-medium">{{ $row->employee?->name ?? '—' }}</flux:table.cell>
                    <flux:table.cell class="text-sm">{{ $months[$row->period_month] ?? $row->period_month }} {{ $row->period_year }}</flux:table.cell>
                    <flux:table.cell class="text-right font-mono text-sm">{{ number_format($row->gross_amount, 2) }}</flux:table.cell>
                    <flux:table.cell class="text-right font-mono text-sm text-red-600 dark:text-red-400">{{ number_format($row->deductions_amount, 2) }}</flux:table.cell>
                    <flux:table.cell class="text-right font-mono text-sm font-medium">{{ number_format($row->net_amount, 2) }}</flux:table.cell>
                    <flux:table.cell>
                        @php($statusColor = match ($row->status) { 'draft' => 'zinc', 'finalized' => 'amber', 'paid' => 'lime', default => 'zinc' })
                        <flux:badge :color="$statusColor" size="sm">{{ $statuses[$row->status] ?? $row->status }}</flux:badge>
                    </flux:table.cell>
                    <flux:table.cell>
                        <div class="flex items-center justify-end gap-1">
                            @can('payroll.update')
                                <flux:button size="sm" variant="ghost" icon="pencil-square" :href="route('payroll.edit', $row)" wire:navigate>Edit</flux:button>
                            @endcan
                            @can('payroll.delete')
                                <flux:modal.trigger :name="'payroll-delete-' . $row->id">
                                    <flux:button size="sm" variant="ghost" icon="trash" />
                                </flux:modal.trigger>
                                <flux:modal :name="'payroll-delete-' . $row->id">
                                    <div class="space-y-4">
                                        <flux:heading size="lg">Delete payroll #{{ str_pad($row->id, 5, '0', STR_PAD_LEFT) }}?</flux:heading>
                                        <flux:text>Cannot be undone.</flux:text>
                                        <div class="flex gap-2 justify-end">
                                            <flux:modal.close><flux:button variant="ghost">Cancel</flux:button></flux:modal.close>
                                            <flux:button variant="danger" wire:click="delete({{ $row->id }})" x-on:click="$flux.modal('payroll-delete-{{ $row->id }}').close()">Delete</flux:button>
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
                        <flux:icon.banknotes class="mx-auto mb-3 size-8 text-zinc-400" />
                        <div class="font-medium">No payroll entries yet</div>
                        <flux:text class="mt-1">Add one to start the month.</flux:text>
                    </flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>

    @if ($rows->hasPages())
        <div class="mt-4"><flux:pagination :paginator="$rows" /></div>
    @endif

</div>
