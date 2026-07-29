<div>
    <div class="mb-6 flex items-start justify-between gap-4">
        <div>
            <flux:heading size="xl" level="1">Advance Receipt Entry</flux:heading>
            <flux:text class="mt-1">Advance payments recorded by the cashier (MQ/AR series).</flux:text>
        </div>
        <div class="flex items-center gap-2">
            @can('advance_receipt.view')
                <flux:button variant="ghost" icon="arrow-down-tray" wire:click="download">Report</flux:button>
            @endcan
            @can('advance_receipt.create')
                <flux:button variant="primary" icon="plus" :href="route('advance-receipt.create')" wire:navigate>New Receipt</flux:button>
            @endcan
        </div>
    </div>

    <div class="mb-6 grid grid-cols-2 md:grid-cols-4 gap-3">
        <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 p-4">
            <div class="flex items-center gap-2 text-zinc-500"><flux:icon.receipt-percent class="size-4" /><flux:text size="sm">Active Receipts</flux:text></div>
            <div class="mt-1 text-2xl font-semibold tabular-nums">{{ $kpis['count'] }}</div>
        </div>
        <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 p-4">
            <div class="flex items-center gap-2 text-zinc-500"><flux:icon.banknotes class="size-4" /><flux:text size="sm">Advance Received</flux:text></div>
            <div class="mt-1 text-2xl font-semibold tabular-nums font-mono">₹ {{ number_format($kpis['received'], 0) }}</div>
        </div>
        <button type="button" wire:click="$set('statusFilter', 'partially_received')"
            class="text-left rounded-lg border border-zinc-200 dark:border-zinc-700 p-4 hover:border-zinc-400 dark:hover:border-zinc-500 transition">
            <div class="flex items-center gap-2 text-zinc-500"><flux:icon.clock class="size-4" /><flux:text size="sm">Partially Received</flux:text></div>
            <div class="mt-1 text-2xl font-semibold tabular-nums">{{ $kpis['partial'] }}</div>
        </button>
        <button type="button" wire:click="$set('statusFilter', 'cancelled')"
            class="text-left rounded-lg border border-zinc-200 dark:border-zinc-700 p-4 hover:border-zinc-400 dark:hover:border-zinc-500 transition">
            <div class="flex items-center gap-2 text-zinc-500"><flux:icon.x-circle class="size-4" /><flux:text size="sm">Cancelled / Failed</flux:text></div>
            <div class="mt-1 text-2xl font-semibold tabular-nums">{{ $kpis['cancelled'] }}</div>
        </button>
    </div>

    <div class="mb-4 flex items-center gap-3 flex-wrap">
        <flux:input wire:model.live.debounce.300ms="search" placeholder="Search receipt / ref / cheque / JC no…" icon="magnifying-glass" clearable class="max-w-md" />
        <flux:select wire:model.live="statusFilter" variant="listbox" class="max-w-44">
            <flux:select.option value="all">All status</flux:select.option>
            @foreach ($statuses as $key => $label)
                <flux:select.option :value="$key">{{ $label }}</flux:select.option>
            @endforeach
        </flux:select>
        <flux:select wire:model.live="modeFilter" variant="listbox" searchable class="max-w-48">
            <flux:select.option value="all">All modes</flux:select.option>
            @foreach ($this->paymentModes as $pm)
                <flux:select.option :value="(string) $pm->id">{{ $pm->name }}</flux:select.option>
            @endforeach
        </flux:select>
        @if ($search || $statusFilter !== 'all' || $modeFilter !== 'all')
            <flux:button variant="ghost" size="sm" icon="x-mark" wire:click="clearFilters">Clear</flux:button>
        @endif
    </div>

    <flux:table>
        <flux:table.columns>
            <flux:table.column class="w-40" sortable :sorted="$sortBy === 'receipt_no'" :direction="$sortDirection" wire:click="sort('receipt_no')">Receipt No</flux:table.column>
            <flux:table.column class="w-28">Job Card</flux:table.column>
            <flux:table.column>Customer</flux:table.column>
            <flux:table.column>Mode</flux:table.column>
            <flux:table.column class="w-32 text-end" sortable :sorted="$sortBy === 'amount'" :direction="$sortDirection" wire:click="sort('amount')">Amount</flux:table.column>
            <flux:table.column class="w-36">Status</flux:table.column>
            <flux:table.column class="w-28 text-end">Actions</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @forelse ($rows as $row)
                <flux:table.row :key="$row->id">
                    <flux:table.cell class="font-mono text-xs">{{ $row->receipt_no ?? '—' }}</flux:table.cell>
                    <flux:table.cell class="font-mono text-xs">{{ $row->jobCard?->job_card_no ?? '—' }}</flux:table.cell>
                    <flux:table.cell class="text-sm">{{ $row->customer?->name ?? '—' }}</flux:table.cell>
                    <flux:table.cell class="text-sm text-zinc-500">{{ $row->paymentMode?->name ?? '—' }}</flux:table.cell>
                    <flux:table.cell class="text-end font-mono text-sm">₹ {{ number_format((float) $row->amount, 2) }}</flux:table.cell>
                    <flux:table.cell>
                        @php($sc = match ($row->payment_status) {
                            'fully_received' => 'lime',
                            'partially_received' => 'blue',
                            'cancelled', 'failed' => 'red',
                            'refunded' => 'purple',
                            default => 'zinc',
                        })
                        <flux:badge :color="$sc" size="sm">{{ $statuses[$row->payment_status] ?? $row->payment_status }}</flux:badge>
                    </flux:table.cell>
                    <flux:table.cell>
                        <div class="flex items-center justify-end gap-1">
                            @can('advance_receipt.update')
                                <flux:button size="sm" variant="ghost" icon="pencil-square" :href="route('advance-receipt.edit', $row)" wire:navigate>Edit</flux:button>
                            @endcan
                            @can('advance_receipt.delete')
                                <flux:modal.trigger :name="'ar-delete-' . $row->id">
                                    <flux:button size="sm" variant="ghost" icon="trash" />
                                </flux:modal.trigger>
                                <flux:modal :name="'ar-delete-' . $row->id">
                                    <div class="space-y-4">
                                        <flux:heading size="lg">Delete {{ $row->receipt_no }}?</flux:heading>
                                        <flux:text>This cannot be undone. Attachments are removed too.</flux:text>
                                        <div class="flex gap-2 justify-end">
                                            <flux:modal.close><flux:button variant="ghost">Cancel</flux:button></flux:modal.close>
                                            <flux:button variant="danger" wire:click="delete({{ $row->id }})" x-on:click="$flux.modal('ar-delete-{{ $row->id }}').close()">Delete</flux:button>
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
                        <flux:icon.receipt-percent class="mx-auto mb-3 size-8 text-zinc-400" />
                        <div class="font-medium">No advance receipts yet</div>
                        <flux:text class="mt-1">Record an advance payment from a customer.</flux:text>
                    </flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>

    @if ($rows->hasPages())<div class="mt-4"><flux:pagination :paginator="$rows" /></div>@endif
</div>
