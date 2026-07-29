<div>
    <div class="mb-6 flex items-start justify-between gap-4">
        <div>
            <flux:heading size="xl" level="1">Payment Refund</flux:heading>
            <flux:text class="mt-1">Vendor refunds — excess / duplicate payment, cancelled PO, invoice revision or purchase return.</flux:text>
        </div>
        <div class="flex items-center gap-2">
            @can('payment_refund.view')
                <flux:button variant="ghost" icon="arrow-down-tray" wire:click="download">Payment Report</flux:button>
            @endcan
            @can('payment_refund.create')
                <flux:button variant="primary" icon="plus" :href="route('payment-refund.create')" wire:navigate>New Refund</flux:button>
            @endcan
        </div>
    </div>

    <div class="mb-6 grid grid-cols-1 md:grid-cols-3 gap-3">
        <button type="button" wire:click="$set('statusFilter', 'requested')"
            class="text-left rounded-lg border border-zinc-200 dark:border-zinc-700 p-4 hover:border-zinc-400 dark:hover:border-zinc-500 transition">
            <div class="flex items-center gap-2 text-zinc-500"><flux:icon.clock class="size-4" /><flux:text size="sm">Pending Vendor Refunds</flux:text></div>
            <div class="mt-1 text-2xl font-semibold tabular-nums">{{ $kpis['pending'] }}</div>
        </button>
        <button type="button" wire:click="$set('statusFilter', 'refunded')"
            class="text-left rounded-lg border border-zinc-200 dark:border-zinc-700 p-4 hover:border-zinc-400 dark:hover:border-zinc-500 transition">
            <div class="flex items-center gap-2 text-zinc-500"><flux:icon.check-circle class="size-4" /><flux:text size="sm">Refund Received Today</flux:text></div>
            <div class="mt-1 text-2xl font-semibold tabular-nums">{{ $kpis['received_today'] }}</div>
        </button>
        <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 p-4">
            <div class="flex items-center gap-2 text-zinc-500"><flux:icon.banknotes class="size-4" /><flux:text size="sm">Refund Value This Month</flux:text></div>
            <div class="mt-1 text-2xl font-semibold tabular-nums">₹{{ number_format($kpis['value_month'], 0) }}</div>
        </div>
    </div>

    <div class="mb-4 flex items-center gap-3 flex-wrap">
        <flux:input wire:model.live.debounce.300ms="search" placeholder="Search refund / UTR…" icon="magnifying-glass" clearable class="max-w-md" />
        <flux:select wire:model.live="statusFilter" variant="listbox" class="max-w-52">
            <flux:select.option value="all">All status</flux:select.option>
            @foreach ($statuses as $key => $label)<flux:select.option :value="$key">{{ $label }}</flux:select.option>@endforeach
        </flux:select>
        <flux:select wire:model.live="vendorFilter" variant="listbox" class="max-w-56">
            <flux:select.option value="all">All vendors</flux:select.option>
            @foreach ($this->vendors as $vendor)<flux:select.option :value="(string) $vendor->id">{{ $vendor->name }}</flux:select.option>@endforeach
        </flux:select>
        @if ($search || $statusFilter !== 'all' || $vendorFilter !== 'all')
            <flux:button variant="ghost" size="sm" icon="x-mark" wire:click="clearFilters">Clear</flux:button>
        @endif
    </div>

    <flux:table>
        <flux:table.columns>
            <flux:table.column class="w-28" sortable :sorted="$sortBy === 'refund_no'" :direction="$sortDirection" wire:click="sort('refund_no')">Refund</flux:table.column>
            <flux:table.column>Vendor</flux:table.column>
            <flux:table.column class="w-36 text-end" sortable :sorted="$sortBy === 'amount'" :direction="$sortDirection" wire:click="sort('amount')">Amount</flux:table.column>
            <flux:table.column class="w-36">Status</flux:table.column>
            <flux:table.column class="w-28 text-end">Actions</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @forelse ($rows as $row)
                <flux:table.row :key="$row->id">
                    <flux:table.cell class="font-mono text-xs">{{ $row->refund_no }}</flux:table.cell>
                    <flux:table.cell class="text-sm">{{ $row->vendor?->name ?? '—' }}</flux:table.cell>
                    <flux:table.cell class="text-end font-mono text-sm">{{ $row->amount !== null ? number_format((float) $row->amount, 2) : '—' }}</flux:table.cell>
                    <flux:table.cell>
                        @php($sc = match ($row->status) {
                            'refunded' => 'lime',
                            'requested' => 'amber',
                            'on_hold' => 'orange',
                            'rejected', 'cancelled' => 'red',
                            default => 'zinc',
                        })
                        <flux:badge :color="$sc" size="sm">{{ $statuses[$row->status] ?? $row->status }}</flux:badge>
                    </flux:table.cell>
                    <flux:table.cell>
                        <div class="flex items-center justify-end gap-1">
                            @can('payment_refund.update')
                                <flux:button size="sm" variant="ghost" icon="pencil-square" :href="route('payment-refund.edit', $row)" wire:navigate>Edit</flux:button>
                            @endcan
                            @can('payment_refund.delete')
                                <flux:modal.trigger :name="'pr-delete-' . $row->id"><flux:button size="sm" variant="ghost" icon="trash" /></flux:modal.trigger>
                                <flux:modal :name="'pr-delete-' . $row->id">
                                    <div class="space-y-4">
                                        <flux:heading size="lg">Delete {{ $row->refund_no }}?</flux:heading>
                                        <flux:text>This cannot be undone. Attachments are removed too.</flux:text>
                                        <div class="flex gap-2 justify-end">
                                            <flux:modal.close><flux:button variant="ghost">Cancel</flux:button></flux:modal.close>
                                            <flux:button variant="danger" wire:click="delete({{ $row->id }})" x-on:click="$flux.modal('pr-delete-{{ $row->id }}').close()">Delete</flux:button>
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
                        <flux:icon.arrow-uturn-down class="mx-auto mb-3 size-8 text-zinc-400" />
                        <div class="font-medium">No payment refunds yet</div>
                    </flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>

    @if ($rows->hasPages())<div class="mt-4"><flux:pagination :paginator="$rows" /></div>@endif
</div>
