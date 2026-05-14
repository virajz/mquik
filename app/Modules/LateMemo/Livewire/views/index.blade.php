<?php
?>
<div>
    <div class="mb-6 flex items-start justify-between gap-4">
        <div>
            <flux:heading size="xl" level="1">Late Memos</flux:heading>
            <flux:text class="mt-1">Issue + track late-arrival memos by employee.</flux:text>
        </div>
        <div class="flex items-center gap-2">
            @can('late_memo.create')
                <flux:button variant="primary" icon="plus" wire:click="openCreate">New Memo</flux:button>
            @endcan
            <flux:dropdown align="end">
                <flux:button variant="ghost" icon="ellipsis-vertical" />
                <flux:menu>
                    <flux:menu.item icon="arrow-up-tray" wire:click="$dispatch('start-import', { module: 'LateMemo' })">Import…</flux:menu.item>
                    <flux:menu.item icon="arrow-down-tray" wire:click="$dispatch('start-export', { module: 'LateMemo' })">Export</flux:menu.item>
                </flux:menu>
            </flux:dropdown>
        </div>
    </div>

    <livewire:import-export.export-button :module="'LateMemo'" wire:key="export-late-memo" />
    <livewire:import-export.import-wizard :module="'LateMemo'" wire:key="import-late-memo" />

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
        @if ($search || $employeeFilter !== 'all' || $statusFilter !== 'all')
            <flux:button variant="ghost" size="sm" icon="x-mark" wire:click="clearFilters">Clear</flux:button>
        @endif
    </div>

    <flux:table>
        <flux:table.columns>
            <flux:table.column class="w-20" sortable :sorted="$sortBy === 'id'" :direction="$sortDirection" wire:click="sort('id')">ID</flux:table.column>
            <flux:table.column>Employee</flux:table.column>
            <flux:table.column class="w-36" sortable :sorted="$sortBy === 'memo_date'" :direction="$sortDirection" wire:click="sort('memo_date')">Date</flux:table.column>
            <flux:table.column class="w-24 text-right" sortable :sorted="$sortBy === 'late_by_minutes'" :direction="$sortDirection" wire:click="sort('late_by_minutes')">Late by</flux:table.column>
            <flux:table.column>Reason</flux:table.column>
            <flux:table.column class="w-32" sortable :sorted="$sortBy === 'status'" :direction="$sortDirection" wire:click="sort('status')">Status</flux:table.column>
            <flux:table.column class="w-32" align="end">Actions</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @forelse ($rows as $row)
                <flux:table.row :key="$row->id">
                    <flux:table.cell class="font-mono text-xs text-zinc-500">#{{ str_pad($row->id, 5, '0', STR_PAD_LEFT) }}</flux:table.cell>
                    <flux:table.cell class="font-medium">{{ $row->employee?->name ?? '—' }}</flux:table.cell>
                    <flux:table.cell class="text-sm">{{ $row->memo_date?->format('d M Y') }}</flux:table.cell>
                    <flux:table.cell class="text-right font-mono text-sm">{{ $row->late_by_minutes }} min</flux:table.cell>
                    <flux:table.cell class="text-zinc-500 max-w-md truncate">{{ $row->reason }}</flux:table.cell>
                    <flux:table.cell>
                        @php($statusColor = match ($row->status) { 'issued' => 'amber', 'acknowledged' => 'blue', 'waived' => 'lime', default => 'zinc' })
                        <flux:badge :color="$statusColor" size="sm">{{ $statuses[$row->status] ?? $row->status }}</flux:badge>
                    </flux:table.cell>
                    <flux:table.cell>
                        <div class="flex items-center justify-end gap-1">
                            @can('late_memo.update')
                                <flux:button size="sm" variant="ghost" icon="pencil-square" wire:click="openEdit({{ $row->id }})">Edit</flux:button>
                            @endcan
                            @can('late_memo.delete')
                                <flux:modal.trigger :name="'late-memo-delete-' . $row->id">
                                    <flux:button size="sm" variant="ghost" icon="trash" />
                                </flux:modal.trigger>
                                <flux:modal :name="'late-memo-delete-' . $row->id">
                                    <div class="space-y-4">
                                        <flux:heading size="lg">Delete late memo #{{ str_pad($row->id, 5, '0', STR_PAD_LEFT) }}?</flux:heading>
                                        <flux:text>Cannot be undone.</flux:text>
                                        <div class="flex gap-2 justify-end">
                                            <flux:modal.close><flux:button variant="ghost">Cancel</flux:button></flux:modal.close>
                                            <flux:button variant="danger" wire:click="delete({{ $row->id }})" x-on:click="$flux.modal('late-memo-delete-{{ $row->id }}').close()">Delete</flux:button>
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
                        <flux:icon.exclamation-triangle class="mx-auto mb-3 size-8 text-zinc-400" />
                        <div class="font-medium">No late memos yet</div>
                        <flux:text class="mt-1">Issue one to start the audit trail.</flux:text>
                    </flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>

    @if ($rows->hasPages())
        <div class="mt-4"><flux:pagination :paginator="$rows" /></div>
    @endif

    <livewire:late-memo.form />
</div>
