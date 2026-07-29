<div>
    <div class="mb-6 flex items-start justify-between gap-4">
        <div>
            <flux:heading size="xl" level="1">Vendor Purchase Orders</flux:heading>
            <flux:text class="mt-1">Confirmed orders placed on vendors — with acknowledgement and dispatch tracking.</flux:text>
        </div>
        <div class="flex items-center gap-2">
            @can('vendor_purchase_order.view')
                <flux:button variant="ghost" icon="arrow-down-tray" wire:click="download">Purchase Order Report</flux:button>
            @endcan
            @can('vendor_purchase_order.create')
                <flux:button variant="primary" icon="plus" :href="route('vendor-purchase-order.create')" wire:navigate>New PO</flux:button>
            @endcan
        </div>
    </div>

    <div class="mb-6 grid grid-cols-1 md:grid-cols-3 gap-3">
        @foreach ([
            ['label' => 'Pending', 'value' => $kpis['pending'], 'icon' => 'clock', 'filter' => 'pending'],
            ['label' => 'Delayed', 'value' => $kpis['delayed'], 'icon' => 'exclamation-triangle', 'filter' => null],
            ['label' => 'Cancelled', 'value' => $kpis['cancelled'], 'icon' => 'x-circle', 'filter' => 'cancelled'],
        ] as $kpi)
            <button type="button" @if ($kpi['filter']) wire:click="$set('statusFilter', '{{ $kpi['filter'] }}')" @else disabled @endif
                class="text-left rounded-lg border border-zinc-200 dark:border-zinc-700 p-4 @if ($kpi['filter']) hover:border-zinc-400 dark:hover:border-zinc-500 transition @endif">
                <div class="flex items-center gap-2 text-zinc-500"><flux:icon :name="$kpi['icon']" class="size-4" /><flux:text size="sm">{{ $kpi['label'] }}</flux:text></div>
                <div class="mt-1 text-2xl font-semibold tabular-nums">{{ $kpi['value'] }}</div>
            </button>
        @endforeach
    </div>

    <div class="mb-4 flex items-center gap-3 flex-wrap">
        <flux:input wire:model.live.debounce.300ms="search" placeholder="Search PO / consignment / job card…" icon="magnifying-glass" clearable class="max-w-md" />
        <flux:select wire:model.live="statusFilter" variant="listbox" class="max-w-48">
            <flux:select.option value="all">All status</flux:select.option>
            @foreach ($statuses as $key => $label)<flux:select.option :value="$key">{{ $label }}</flux:select.option>@endforeach
        </flux:select>
        <flux:select wire:model.live="ackFilter" variant="listbox" class="max-w-48">
            <flux:select.option value="all">All acknowledgement</flux:select.option>
            @foreach ($acks as $key => $label)<flux:select.option :value="$key">{{ $label }}</flux:select.option>@endforeach
        </flux:select>
        @if ($search || $statusFilter !== 'all' || $ackFilter !== 'all' || $vendorFilter !== 'all')
            <flux:button variant="ghost" size="sm" icon="x-mark" wire:click="clearFilters">Clear</flux:button>
        @endif
    </div>

    <flux:table>
        <flux:table.columns>
            <flux:table.column class="w-28" sortable :sorted="$sortBy === 'po_no'" :direction="$sortDirection" wire:click="sort('po_no')">PO</flux:table.column>
            <flux:table.column>Vendor / Type</flux:table.column>
            <flux:table.column class="w-20 text-end">Lines</flux:table.column>
            <flux:table.column class="w-36">Status</flux:table.column>
            <flux:table.column class="w-32">Acknowledgement</flux:table.column>
            <flux:table.column class="w-28 text-end">Actions</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @forelse ($rows as $row)
                <flux:table.row :key="$row->id">
                    <flux:table.cell class="font-mono text-xs">{{ $row->po_no }}</flux:table.cell>
                    <flux:table.cell class="text-sm">
                        <div>{{ $row->vendor?->name ?? '—' }}</div>
                        <div class="text-xs text-zinc-500 mt-0.5">{{ $poTypes[$row->po_type] ?? '' }}</div>
                    </flux:table.cell>
                    <flux:table.cell class="text-end font-mono text-sm">{{ $row->items_count }}</flux:table.cell>
                    <flux:table.cell>
                        @php($sc = match ($row->status) {
                            'delivered' => 'lime',
                            'dispatched' => 'blue',
                            'acknowledged' => 'sky',
                            'cancelled' => 'red',
                            default => 'zinc',
                        })
                        <flux:badge :color="$sc" size="sm">{{ $statuses[$row->status] ?? $row->status }}</flux:badge>
                    </flux:table.cell>
                    <flux:table.cell>
                        @php($ac = match ($row->acknowledgement_status) {
                            'accepted' => 'lime',
                            'rejected' => 'red',
                            default => 'amber',
                        })
                        <flux:badge :color="$ac" size="sm">{{ $acks[$row->acknowledgement_status] ?? $row->acknowledgement_status }}</flux:badge>
                    </flux:table.cell>
                    <flux:table.cell>
                        <div class="flex items-center justify-end gap-1">
                            @can('vendor_purchase_order.update')
                                <flux:button size="sm" variant="ghost" icon="pencil-square" :href="route('vendor-purchase-order.edit', $row)" wire:navigate>Edit</flux:button>
                            @endcan
                            @can('vendor_purchase_order.delete')
                                <flux:modal.trigger :name="'vpo-delete-' . $row->id"><flux:button size="sm" variant="ghost" icon="trash" /></flux:modal.trigger>
                                <flux:modal :name="'vpo-delete-' . $row->id">
                                    <div class="space-y-4">
                                        <flux:heading size="lg">Delete {{ $row->po_no }}?</flux:heading>
                                        <flux:text>This cannot be undone. Lines, charges and attachments are removed too.</flux:text>
                                        <div class="flex gap-2 justify-end">
                                            <flux:modal.close><flux:button variant="ghost">Cancel</flux:button></flux:modal.close>
                                            <flux:button variant="danger" wire:click="delete({{ $row->id }})" x-on:click="$flux.modal('vpo-delete-{{ $row->id }}').close()">Delete</flux:button>
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
                        <flux:icon.shopping-cart class="mx-auto mb-3 size-8 text-zinc-400" />
                        <div class="font-medium">No purchase orders yet</div>
                    </flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>

    @if ($rows->hasPages())<div class="mt-4"><flux:pagination :paginator="$rows" /></div>@endif
</div>
