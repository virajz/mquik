<div>
    <div class="mb-6 flex items-start justify-between gap-4">
        <div>
            <flux:heading size="xl" level="1">VPO Cancel Requests</flux:heading>
            <flux:text class="mt-1">Ask a vendor to cancel a purchase order and track the response, charge terms and refund.</flux:text>
        </div>
        <div class="flex items-center gap-2">
            @can('vpo_cancel_request.view')
                <flux:button variant="ghost" icon="arrow-down-tray" wire:click="download">Purchase Report</flux:button>
            @endcan
            @can('vpo_cancel_request.create')
                <flux:button variant="primary" icon="plus" :href="route('vpo-cancel-request.create')" wire:navigate>New Cancel Request</flux:button>
            @endcan
        </div>
    </div>

    <div class="mb-6 grid grid-cols-1 md:grid-cols-3 gap-3">
        @foreach ([
            ['label' => 'Open Cancellation Requests', 'value' => $kpis['open'], 'icon' => 'clock'],
            ['label' => 'Vendor Pending Responses', 'value' => $kpis['pending_response'], 'icon' => 'chat-bubble-left-right'],
            ['label' => 'Refund Pending Cases', 'value' => $kpis['refund_pending'], 'icon' => 'banknotes'],
        ] as $kpi)
            <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 p-4">
                <div class="flex items-center gap-2 text-zinc-500"><flux:icon :name="$kpi['icon']" class="size-4" /><flux:text size="sm">{{ $kpi['label'] }}</flux:text></div>
                <div class="mt-1 text-2xl font-semibold tabular-nums">{{ $kpi['value'] }}</div>
            </div>
        @endforeach
    </div>

    <div class="mb-4 flex items-center gap-3 flex-wrap">
        <flux:input wire:model.live.debounce.300ms="search" placeholder="Search request / PO…" icon="magnifying-glass" clearable class="max-w-md" />
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
            <flux:table.column class="w-28" sortable :sorted="$sortBy === 'request_no'" :direction="$sortDirection" wire:click="sort('request_no')">Request</flux:table.column>
            <flux:table.column>Vendor / PO</flux:table.column>
            <flux:table.column class="w-20 text-end">Lines</flux:table.column>
            <flux:table.column class="w-44">Status</flux:table.column>
            <flux:table.column class="w-28 text-end">Actions</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @forelse ($rows as $row)
                <flux:table.row :key="$row->id">
                    <flux:table.cell class="font-mono text-xs">{{ $row->request_no }}</flux:table.cell>
                    <flux:table.cell class="text-sm">
                        <div>{{ $row->vendor?->name ?? '—' }}</div>
                        <div class="text-xs text-zinc-500 mt-0.5 font-mono">{{ $row->purchaseOrder?->po_no ?? '' }}</div>
                    </flux:table.cell>
                    <flux:table.cell class="text-end font-mono text-sm">{{ $row->items_count }}</flux:table.cell>
                    <flux:table.cell>
                        @php($sc = match ($row->status) {
                            'fully_accepted', 'vendor_accepted' => 'lime',
                            'partially_accepted' => 'blue',
                            'vendor_rejected' => 'red',
                            'vendor_reviewing', 'requested_to_vendor' => 'amber',
                            'cancelled' => 'zinc',
                            default => 'zinc',
                        })
                        <flux:badge :color="$sc" size="sm">{{ $statuses[$row->status] ?? $row->status }}</flux:badge>
                    </flux:table.cell>
                    <flux:table.cell>
                        <div class="flex items-center justify-end gap-1">
                            @can('vpo_cancel_request.update')
                                <flux:button size="sm" variant="ghost" icon="pencil-square" :href="route('vpo-cancel-request.edit', $row)" wire:navigate>Edit</flux:button>
                            @endcan
                            @can('vpo_cancel_request.delete')
                                <flux:modal.trigger :name="'vcr-delete-' . $row->id"><flux:button size="sm" variant="ghost" icon="trash" /></flux:modal.trigger>
                                <flux:modal :name="'vcr-delete-' . $row->id">
                                    <div class="space-y-4">
                                        <flux:heading size="lg">Delete {{ $row->request_no }}?</flux:heading>
                                        <flux:text>This cannot be undone. Lines and attachments are removed too.</flux:text>
                                        <div class="flex gap-2 justify-end">
                                            <flux:modal.close><flux:button variant="ghost">Cancel</flux:button></flux:modal.close>
                                            <flux:button variant="danger" wire:click="delete({{ $row->id }})" x-on:click="$flux.modal('vcr-delete-{{ $row->id }}').close()">Delete</flux:button>
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
                        <flux:icon.x-circle class="mx-auto mb-3 size-8 text-zinc-400" />
                        <div class="font-medium">No cancel requests yet</div>
                    </flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>

    @if ($rows->hasPages())<div class="mt-4"><flux:pagination :paginator="$rows" /></div>@endif
</div>
