<div>
    <div class="mb-6 flex items-start justify-between gap-4">
        <div>
            <flux:heading size="xl" level="1">Regular Receipts</flux:heading>
            <flux:text class="mt-1">Customer payment receipts — payment mode, cheque tracking, differences and attachments.</flux:text>
        </div>
        @can('regular_receipt.create')
            <flux:button variant="primary" icon="plus" :href="route('regular-receipt.create')" wire:navigate>New Receipt</flux:button>
        @endcan
    </div>

    <div class="mb-4 flex items-center gap-3 flex-wrap">
        <flux:input wire:model.live.debounce.300ms="search" placeholder="Search by receipt no, ref, cheque or customer…" icon="magnifying-glass" clearable class="max-w-md" />
        <flux:select wire:model.live="statusFilter" variant="listbox" class="max-w-40">
            <flux:select.option value="all">All status</flux:select.option>
            @foreach ($statuses as $key => $label)
                <flux:select.option :value="$key">{{ $label }}</flux:select.option>
            @endforeach
        </flux:select>
        <flux:select wire:model.live="chequeFilter" variant="listbox" class="max-w-44">
            <flux:select.option value="all">All cheques</flux:select.option>
            @foreach ($chequeStatuses as $key => $label)
                <flux:select.option :value="$key">{{ $label }}</flux:select.option>
            @endforeach
        </flux:select>
        @if ($search || $statusFilter !== 'all' || $chequeFilter !== 'all')
            <flux:button variant="ghost" size="sm" icon="x-mark" wire:click="clearFilters">Clear</flux:button>
        @endif
    </div>

    <flux:table>
        <flux:table.columns>
            <flux:table.column class="w-44" sortable :sorted="$sortBy === 'receipt_no'" :direction="$sortDirection" wire:click="sort('receipt_no')">Receipt No.</flux:table.column>
            <flux:table.column>Customer</flux:table.column>
            <flux:table.column class="w-32">Mode</flux:table.column>
            <flux:table.column class="w-32 text-right" sortable :sorted="$sortBy === 'amount'" :direction="$sortDirection" wire:click="sort('amount')">Amount</flux:table.column>
            <flux:table.column class="w-32">Cheque</flux:table.column>
            <flux:table.column class="w-28" sortable :sorted="$sortBy === 'status'" :direction="$sortDirection" wire:click="sort('status')">Status</flux:table.column>
            <flux:table.column class="w-32" align="end">Actions</flux:table.column>
        </flux:table.columns>
        <flux:table.rows>
            @forelse ($rows as $row)
                <flux:table.row :key="$row->id">
                    <flux:table.cell class="font-mono text-xs">{{ $row->receipt_no ?? '—' }}</flux:table.cell>
                    <flux:table.cell class="text-sm font-medium">{{ trim($row->customer?->first_name.' '.($row->customer?->last_name ?? '')) }}</flux:table.cell>
                    <flux:table.cell class="text-sm text-zinc-500">{{ $row->paymentMode?->name ?? '—' }}</flux:table.cell>
                    <flux:table.cell class="text-right font-mono text-sm">{{ number_format((float) $row->amount, 2) }}</flux:table.cell>
                    <flux:table.cell>
                        @if ($row->cheque_status)
                            @php($cc = match ($row->cheque_status) {
                                'cleared' => 'lime', 'returned' => 'red', 'cancelled' => 'zinc', default => 'sky',
                            })
                            <flux:badge :color="$cc" size="sm">{{ $chequeStatuses[$row->cheque_status] ?? $row->cheque_status }}</flux:badge>
                        @else
                            <span class="text-zinc-400 text-xs">—</span>
                        @endif
                    </flux:table.cell>
                    <flux:table.cell>
                        @php($sc = match ($row->status) {
                            'confirmed' => 'green', 'cancelled' => 'red', default => 'sky',
                        })
                        <flux:badge :color="$sc" size="sm">{{ $statuses[$row->status] ?? $row->status }}</flux:badge>
                    </flux:table.cell>
                    <flux:table.cell>
                        <div class="flex items-center justify-end gap-1">
                            @can('regular_receipt.update')
                                <flux:button size="sm" variant="ghost" icon="pencil-square" :href="route('regular-receipt.edit', $row)" wire:navigate>Edit</flux:button>
                            @endcan
                            @can('regular_receipt.delete')
                                <flux:modal.trigger :name="'rcpt-delete-' . $row->id">
                                    <flux:button size="sm" variant="ghost" icon="trash" />
                                </flux:modal.trigger>
                                <flux:modal :name="'rcpt-delete-' . $row->id">
                                    <div class="space-y-4">
                                        <flux:heading size="lg">Delete {{ $row->receipt_no }}?</flux:heading>
                                        <flux:text>Cannot be undone. Cascades to attachments.</flux:text>
                                        <div class="flex gap-2 justify-end">
                                            <flux:modal.close><flux:button variant="ghost">Cancel</flux:button></flux:modal.close>
                                            <flux:button variant="danger" wire:click="delete({{ $row->id }})" x-on:click="$flux.modal('rcpt-delete-{{ $row->id }}').close()">Delete</flux:button>
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
                        <flux:icon.banknotes class="mx-auto mb-3 size-8 text-zinc-400" />
                        <div class="font-medium">No receipts yet</div>
                        <flux:text class="mt-1">Record a customer payment against an invoice.</flux:text>
                    </flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>

    @if ($rows->hasPages())<div class="mt-4"><flux:pagination :paginator="$rows" /></div>@endif
</div>
