<div>
    <div class="mb-6 flex items-start justify-between gap-4">
        <div>
            <flux:heading size="xl" level="1">Outside Labour Bill Verification</flux:heading>
            <flux:text class="mt-1">Receive an outside vendor invoice and verify rate / qty / job match before payment.</flux:text>
        </div>
        <div class="flex items-center gap-2">
            @can('outside_labour_bill.view')
                <flux:button variant="ghost" icon="arrow-down-tray" wire:click="download">Outside Labour Status Report</flux:button>
            @endcan
            @can('outside_labour_bill.create')
                <flux:button variant="primary" icon="plus" :href="route('outside-labour-bill.create')" wire:navigate>Receive Bill</flux:button>
            @endcan
        </div>
    </div>

    <div class="mb-6 grid grid-cols-1 md:grid-cols-3 gap-3">
        @foreach ([
            ['label' => 'Bill Pending to Receive', 'value' => $kpis['pending'], 'icon' => 'clock', 'filter' => 'requested'],
            ['label' => 'On Hold', 'value' => $kpis['on_hold'], 'icon' => 'pause-circle', 'filter' => 'on_hold'],
            ['label' => 'Rejected', 'value' => $kpis['rejected'], 'icon' => 'x-circle', 'filter' => 'rejected'],
        ] as $kpi)
            <button type="button" wire:click="$set('statusFilter', '{{ $kpi['filter'] }}')"
                class="text-left rounded-lg border border-zinc-200 dark:border-zinc-700 p-4 hover:border-zinc-400 dark:hover:border-zinc-500 transition">
                <div class="flex items-center gap-2 text-zinc-500"><flux:icon :name="$kpi['icon']" class="size-4" /><flux:text size="sm">{{ $kpi['label'] }}</flux:text></div>
                <div class="mt-1 text-2xl font-semibold tabular-nums">{{ $kpi['value'] }}</div>
            </button>
        @endforeach
    </div>

    <div class="mb-4 flex items-center gap-3 flex-wrap">
        <flux:input wire:model.live.debounce.300ms="search" placeholder="Search bill / vendor bill no…" icon="magnifying-glass" clearable class="max-w-md" />
        <flux:select wire:model.live="statusFilter" variant="listbox" class="max-w-56">
            <flux:select.option value="all">All status</flux:select.option>
            @foreach ($statuses as $key => $label)<flux:select.option :value="$key">{{ $label }}</flux:select.option>@endforeach
        </flux:select>
        @if ($search || $statusFilter !== 'all' || $vendorFilter !== 'all')
            <flux:button variant="ghost" size="sm" icon="x-mark" wire:click="clearFilters">Clear</flux:button>
        @endif
    </div>

    <flux:table>
        <flux:table.columns>
            <flux:table.column class="w-28" sortable :sorted="$sortBy === 'bill_no'" :direction="$sortDirection" wire:click="sort('bill_no')">Bill</flux:table.column>
            <flux:table.column>Vendor / OL Order</flux:table.column>
            <flux:table.column class="w-32 text-end" sortable :sorted="$sortBy === 'bill_amount'" :direction="$sortDirection" wire:click="sort('bill_amount')">Amount</flux:table.column>
            <flux:table.column class="w-40">Status</flux:table.column>
            <flux:table.column class="w-28 text-end">Actions</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @forelse ($rows as $row)
                <flux:table.row :key="$row->id">
                    <flux:table.cell class="font-mono text-xs">{{ $row->bill_no }}</flux:table.cell>
                    <flux:table.cell class="text-sm">
                        <div>{{ $row->vendor?->name ?? '—' }}</div>
                        <div class="text-xs text-zinc-500 mt-0.5 font-mono">{{ $row->order?->order_no ?? '' }}</div>
                    </flux:table.cell>
                    <flux:table.cell class="text-end font-mono text-sm">{{ $row->bill_amount !== null ? number_format((float) $row->bill_amount, 2) : '—' }}</flux:table.cell>
                    <flux:table.cell>
                        @php($sc = match ($row->status) {
                            'fully_verified' => 'lime',
                            'partially_verified' => 'blue',
                            'under_verification', 'requested' => 'amber',
                            'on_hold' => 'orange',
                            'rejected', 'cancelled' => 'red',
                            default => 'zinc',
                        })
                        <flux:badge :color="$sc" size="sm">{{ $statuses[$row->status] ?? $row->status }}</flux:badge>
                    </flux:table.cell>
                    <flux:table.cell>
                        <div class="flex items-center justify-end gap-1">
                            @can('outside_labour_bill.update')
                                <flux:button size="sm" variant="ghost" icon="pencil-square" :href="route('outside-labour-bill.edit', $row)" wire:navigate>Edit</flux:button>
                            @endcan
                            @can('outside_labour_bill.delete')
                                <flux:modal.trigger :name="'olb-delete-' . $row->id"><flux:button size="sm" variant="ghost" icon="trash" /></flux:modal.trigger>
                                <flux:modal :name="'olb-delete-' . $row->id">
                                    <div class="space-y-4">
                                        <flux:heading size="lg">Delete {{ $row->bill_no }}?</flux:heading>
                                        <flux:text>This cannot be undone. Lines and attachments are removed too.</flux:text>
                                        <div class="flex gap-2 justify-end">
                                            <flux:modal.close><flux:button variant="ghost">Cancel</flux:button></flux:modal.close>
                                            <flux:button variant="danger" wire:click="delete({{ $row->id }})" x-on:click="$flux.modal('olb-delete-{{ $row->id }}').close()">Delete</flux:button>
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
                        <flux:icon.document-check class="mx-auto mb-3 size-8 text-zinc-400" />
                        <div class="font-medium">No bills received yet</div>
                    </flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>

    @if ($rows->hasPages())<div class="mt-4"><flux:pagination :paginator="$rows" /></div>@endif
</div>
