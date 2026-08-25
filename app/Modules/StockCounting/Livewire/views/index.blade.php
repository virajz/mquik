<div>
    <div class="mb-6 flex items-start justify-between gap-4">
        <div>
            <flux:heading size="xl" level="1">Stock Counting</flux:heading>
            <flux:text class="mt-1">Physical &amp; system stock verification — variance by spare, with reasons.</flux:text>
        </div>
        <div class="flex items-center gap-2">
            @can('stock_counting.view')
                <flux:button variant="ghost" icon="arrow-down-tray" wire:click="download">Stock Report</flux:button>
            @endcan
            @can('stock_counting.create')
                <flux:button variant="primary" icon="plus" :href="route('stock-counting.create')" wire:navigate>New Count</flux:button>
            @endcan
        </div>
    </div>

    <div class="mb-6 grid grid-cols-2 md:grid-cols-4 gap-3">
        @foreach ([
            ['label' => 'In Progress', 'value' => $kpis['in_progress'], 'icon' => 'clipboard-document-list'],
            ['label' => 'Completed Today', 'value' => $kpis['completed_today'], 'icon' => 'check-circle'],
            ['label' => 'Total Mismatch Qty', 'value' => number_format($kpis['total_mismatch_qty'], 0), 'icon' => 'chart-bar', 'danger' => true],
            ['label' => 'Pending', 'value' => $kpis['pending'], 'icon' => 'clipboard-document-check'],
        ] as $kpi)
            <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 p-4">
                <div class="flex items-center gap-2 text-zinc-500"><flux:icon :name="$kpi['icon']" class="size-4" /><flux:text size="sm">{{ $kpi['label'] }}</flux:text></div>
                <div class="mt-1 text-2xl font-semibold tabular-nums @if (($kpi['danger'] ?? false) && (float) str_replace(',', '', (string) $kpi['value']) > 0) text-amber-600 dark:text-amber-400 @endif">{{ $kpi['value'] }}</div>
            </div>
        @endforeach
    </div>

    <div class="mb-4 flex items-center gap-3 flex-wrap">
        <flux:input wire:model.live.debounce.300ms="search" placeholder="Search count no…" icon="magnifying-glass" clearable class="max-w-md" />
        <flux:select wire:model.live="statusFilter" variant="listbox" class="max-w-44">
            <flux:select.option value="all">All status</flux:select.option>
            @foreach ($statuses as $key => $label)<flux:select.option :value="$key">{{ $label }}</flux:select.option>@endforeach
        </flux:select>
        @if ($search || $statusFilter !== 'all')
            <flux:button variant="ghost" size="sm" icon="x-mark" wire:click="clearFilters">Clear</flux:button>
        @endif
    </div>

    <flux:table>
        <flux:table.columns>
            <flux:table.column class="w-32" sortable :sorted="$sortBy === 'count_no'" :direction="$sortDirection" wire:click="sort('count_no')">Count No</flux:table.column>
            <flux:table.column>Location / Group</flux:table.column>
            <flux:table.column class="w-40" sortable :sorted="$sortBy === 'count_start_date'" :direction="$sortDirection" wire:click="sort('count_start_date')">Start</flux:table.column>
            <flux:table.column class="w-20 text-end">Items</flux:table.column>
            <flux:table.column class="w-28">Status</flux:table.column>
            <flux:table.column class="w-28 text-end">Actions</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @forelse ($rows as $row)
                <flux:table.row :key="$row->id">
                    <flux:table.cell class="font-mono text-xs">{{ $row->count_no }}</flux:table.cell>
                    <flux:table.cell class="text-sm">
                        <div>{{ $row->storageLocation?->name ?? '—' }}</div>
                        <div class="text-xs text-zinc-500 mt-0.5">{{ $row->inventoryGroup?->name ?? '' }}</div>
                    </flux:table.cell>
                    <flux:table.cell class="text-sm">{{ $row->count_start_date?->format('d/m/Y') ?? '—' }}</flux:table.cell>
                    <flux:table.cell class="text-end font-mono text-sm">{{ $row->items_count }}</flux:table.cell>
                    <flux:table.cell>
                        @php($sc = match ($row->verification_status) {
                            'completed' => 'lime',
                            'in_progress' => 'blue',
                            'pending' => 'amber',
                            'cancelled' => 'red',
                            default => 'zinc',
                        })
                        <flux:badge :color="$sc" size="sm">{{ $statuses[$row->verification_status] ?? $row->verification_status }}</flux:badge>
                    </flux:table.cell>
                    <flux:table.cell>
                        <div class="flex items-center justify-end gap-1">
                            @can('stock_counting.update')
                                <flux:button size="sm" variant="ghost" icon="pencil-square" :href="route('stock-counting.edit', $row)" wire:navigate>Edit</flux:button>
                            @endcan
                            @can('stock_counting.delete')
                                <flux:modal.trigger :name="'sc-delete-' . $row->id"><flux:button size="sm" variant="ghost" icon="trash" /></flux:modal.trigger>
                                <flux:modal :name="'sc-delete-' . $row->id">
                                    <div class="space-y-4">
                                        <flux:heading size="lg">Delete {{ $row->count_no }}?</flux:heading>
                                        <flux:text>This cannot be undone. Counted items and attachments are removed too.</flux:text>
                                        <div class="flex gap-2 justify-end">
                                            <flux:modal.close><flux:button variant="ghost">Cancel</flux:button></flux:modal.close>
                                            <flux:button variant="danger" wire:click="delete({{ $row->id }})" x-on:click="$flux.modal('sc-delete-{{ $row->id }}').close()">Delete</flux:button>
                                        </div>
                                    </div>
                                </flux:modal>
                            @endcan
                        </div>
                    </flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="6" class="text-center text-zinc-500 py-12">
                        <flux:icon.chart-bar class="mx-auto mb-3 size-8 text-zinc-400" />
                        <div class="font-medium">No stock counts yet</div>
                    </flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>

    @if ($rows->hasPages())<div class="mt-4"><flux:pagination :paginator="$rows" /></div>@endif
</div>
