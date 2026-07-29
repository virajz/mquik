<div>
    <div class="mb-6 flex items-start justify-between gap-4">
        <div>
            <flux:heading size="xl" level="1">Excess Stock Approval</flux:heading>
            <flux:text class="mt-1">Admin approval to return or write off excess / dead stock.</flux:text>
        </div>
        <div class="flex items-center gap-2">
            @can('excess_stock_approval.view')
                <flux:button variant="ghost" icon="arrow-down-tray" wire:click="download">Excess Stock Report</flux:button>
            @endcan
            @can('excess_stock_approval.create')
                <flux:button variant="primary" icon="plus" :href="route('excess-stock-approval.create')" wire:navigate>New Request</flux:button>
            @endcan
        </div>
    </div>

    <div class="mb-4 grid grid-cols-1 md:grid-cols-3 gap-3">
        @foreach ([
            ['label' => 'Approval Pending', 'value' => $kpis['pending'], 'icon' => 'clock', 'filter' => 'requested'],
            ['label' => 'Approval Accepted', 'value' => $kpis['approved'], 'icon' => 'check-circle', 'filter' => 'approved'],
            ['label' => 'Approval Rejected', 'value' => $kpis['rejected'], 'icon' => 'x-circle', 'filter' => 'rejected'],
        ] as $kpi)
            <button type="button" wire:click="$set('statusFilter', '{{ $kpi['filter'] }}')"
                class="text-left rounded-lg border border-zinc-200 dark:border-zinc-700 p-4 hover:border-zinc-400 dark:hover:border-zinc-500 transition">
                <div class="flex items-center gap-2 text-zinc-500"><flux:icon :name="$kpi['icon']" class="size-4" /><flux:text size="sm">{{ $kpi['label'] }}</flux:text></div>
                <div class="mt-1 text-2xl font-semibold tabular-nums">{{ $kpi['value'] }}</div>
            </button>
        @endforeach
    </div>

    <div class="mb-6 grid grid-cols-1 md:grid-cols-2 gap-3">
        <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 p-4">
            <div class="flex items-center gap-2 text-zinc-500"><flux:icon.cube class="size-4" /><flux:text size="sm">Excess Stock Value</flux:text></div>
            <div class="mt-1 text-xl font-semibold tabular-nums">₹{{ number_format($kpis['excess_value'], 0) }}</div>
        </div>
        <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 p-4">
            <div class="flex items-center gap-2 text-zinc-500"><flux:icon.archive-box-x-mark class="size-4" /><flux:text size="sm">Dead Stock Value</flux:text></div>
            <div class="mt-1 text-xl font-semibold tabular-nums @if ($kpis['dead_value'] > 0) text-red-600 dark:text-red-400 @endif">₹{{ number_format($kpis['dead_value'], 0) }}</div>
        </div>
    </div>

    <div class="mb-4 flex items-center gap-3 flex-wrap">
        <flux:input wire:model.live.debounce.300ms="search" placeholder="Search request / purchase invoice…" icon="magnifying-glass" clearable class="max-w-md" />
        <flux:select wire:model.live="statusFilter" variant="listbox" class="max-w-56">
            <flux:select.option value="all">All status</flux:select.option>
            @foreach ($statuses as $key => $label)<flux:select.option :value="$key">{{ $label }}</flux:select.option>@endforeach
        </flux:select>
        <flux:select wire:model.live="reasonFilter" variant="listbox" class="max-w-56">
            <flux:select.option value="all">All reasons</flux:select.option>
            @foreach ($reasons as $key => $label)<flux:select.option :value="$key">{{ $label }}</flux:select.option>@endforeach
        </flux:select>
        @if ($search || $statusFilter !== 'all' || $reasonFilter !== 'all')
            <flux:button variant="ghost" size="sm" icon="x-mark" wire:click="clearFilters">Clear</flux:button>
        @endif
    </div>

    <flux:table>
        <flux:table.columns>
            <flux:table.column class="w-28" sortable :sorted="$sortBy === 'request_no'" :direction="$sortDirection" wire:click="sort('request_no')">Request</flux:table.column>
            <flux:table.column>Reason / GRN</flux:table.column>
            <flux:table.column class="w-20 text-end">Lines</flux:table.column>
            <flux:table.column class="w-48">Status</flux:table.column>
            <flux:table.column class="w-28 text-end">Actions</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @forelse ($rows as $row)
                <flux:table.row :key="$row->id">
                    <flux:table.cell class="font-mono text-xs">{{ $row->request_no }}</flux:table.cell>
                    <flux:table.cell class="text-sm">
                        <div>{{ $reasons[$row->excess_stock_reason] ?? '—' }}</div>
                        <div class="text-xs text-zinc-500 mt-0.5 font-mono">{{ $row->goodsReceipt?->grn_no ?? '' }}</div>
                    </flux:table.cell>
                    <flux:table.cell class="text-end font-mono text-sm">{{ $row->items_count }}</flux:table.cell>
                    <flux:table.cell>
                        @php($sc = match ($row->status) {
                            'approved' => 'lime',
                            'under_review', 'requested' => 'amber',
                            'verbal_clarification' => 'sky',
                            'on_hold' => 'orange',
                            'rejected' => 'red',
                            default => 'zinc',
                        })
                        <flux:badge :color="$sc" size="sm">{{ $statuses[$row->status] ?? $row->status }}</flux:badge>
                    </flux:table.cell>
                    <flux:table.cell>
                        <div class="flex items-center justify-end gap-1">
                            @can('excess_stock_approval.update')
                                <flux:button size="sm" variant="ghost" icon="pencil-square" :href="route('excess-stock-approval.edit', $row)" wire:navigate>Edit</flux:button>
                            @endcan
                            @can('excess_stock_approval.delete')
                                <flux:modal.trigger :name="'esa-delete-' . $row->id"><flux:button size="sm" variant="ghost" icon="trash" /></flux:modal.trigger>
                                <flux:modal :name="'esa-delete-' . $row->id">
                                    <div class="space-y-4">
                                        <flux:heading size="lg">Delete {{ $row->request_no }}?</flux:heading>
                                        <flux:text>This cannot be undone. Lines and attachments are removed too.</flux:text>
                                        <div class="flex gap-2 justify-end">
                                            <flux:modal.close><flux:button variant="ghost">Cancel</flux:button></flux:modal.close>
                                            <flux:button variant="danger" wire:click="delete({{ $row->id }})" x-on:click="$flux.modal('esa-delete-{{ $row->id }}').close()">Delete</flux:button>
                                        </div>
                                    </div>
                                </flux:modal>
                            @endcan
                        </div>
                    </flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="5" class="text-center text-zinc-500 py-12">
                        <flux:icon.archive-box-x-mark class="mx-auto mb-3 size-8 text-zinc-400" />
                        <div class="font-medium">No excess stock requests yet</div>
                    </flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>

    @if ($rows->hasPages())<div class="mt-4"><flux:pagination :paginator="$rows" /></div>@endif
</div>
