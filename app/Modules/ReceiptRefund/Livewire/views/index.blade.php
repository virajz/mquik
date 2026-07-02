<div>
    <div class="mb-6 flex items-start justify-between gap-4">
        <div>
            <flux:heading size="xl" level="1">Receipt Refunds</flux:heading>
            <flux:text class="mt-1">Customer refund requests &amp; responses — overpayment or sales-return refunds, cheque tracking and approvals.</flux:text>
        </div>
        @can('receipt_refund.create')
            <flux:button variant="primary" icon="plus" :href="route('receipt-refund.create')" wire:navigate>New Refund</flux:button>
        @endcan
    </div>

    <div class="mb-4 flex items-center gap-3 flex-wrap">
        <flux:input wire:model.live.debounce.300ms="search" placeholder="Search by refund no, ref or customer…" icon="magnifying-glass" clearable class="max-w-md" />
        <flux:select wire:model.live="statusFilter" variant="listbox" class="max-w-44">
            <flux:select.option value="all">All status</flux:select.option>
            @foreach ($refundStatuses as $key => $label)
                <flux:select.option :value="$key">{{ $label }}</flux:select.option>
            @endforeach
        </flux:select>
        @if ($search || $statusFilter !== 'all')
            <flux:button variant="ghost" size="sm" icon="x-mark" wire:click="clearFilters">Clear</flux:button>
        @endif
    </div>

    <flux:table>
        <flux:table.columns>
            <flux:table.column class="w-44" sortable :sorted="$sortBy === 'refund_no'" :direction="$sortDirection" wire:click="sort('refund_no')">Refund No.</flux:table.column>
            <flux:table.column>Customer / Type</flux:table.column>
            <flux:table.column class="w-32 text-right" sortable :sorted="$sortBy === 'amount'" :direction="$sortDirection" wire:click="sort('amount')">Amount</flux:table.column>
            <flux:table.column class="w-32" sortable :sorted="$sortBy === 'refund_status'" :direction="$sortDirection" wire:click="sort('refund_status')">Status</flux:table.column>
            <flux:table.column class="w-32" align="end">Actions</flux:table.column>
        </flux:table.columns>
        <flux:table.rows>
            @forelse ($rows as $row)
                <flux:table.row :key="$row->id">
                    <flux:table.cell class="font-mono text-xs">{{ $row->refund_no ?? '—' }}</flux:table.cell>
                    <flux:table.cell>
                        <div class="text-sm font-medium">{{ trim($row->customer?->first_name.' '.($row->customer?->last_name ?? '')) }}</div>
                        @if ($row->refundType)<div class="text-xs text-zinc-500 mt-0.5">{{ $row->refundType->name }}</div>@endif
                    </flux:table.cell>
                    <flux:table.cell class="text-right font-mono text-sm">{{ number_format((float) $row->amount, 2) }}</flux:table.cell>
                    <flux:table.cell>
                        @php($sc = match ($row->refund_status) {
                            'refunded' => 'lime', 'on_hold' => 'amber', 'rejected' => 'red', 'cancelled' => 'zinc', default => 'sky',
                        })
                        <flux:badge :color="$sc" size="sm">{{ $refundStatuses[$row->refund_status] ?? $row->refund_status }}</flux:badge>
                    </flux:table.cell>
                    <flux:table.cell>
                        <div class="flex items-center justify-end gap-1">
                            @can('receipt_refund.update')
                                <flux:button size="sm" variant="ghost" icon="pencil-square" :href="route('receipt-refund.edit', $row)" wire:navigate>Edit</flux:button>
                            @endcan
                            @can('receipt_refund.delete')
                                <flux:modal.trigger :name="'rf-delete-' . $row->id">
                                    <flux:button size="sm" variant="ghost" icon="trash" />
                                </flux:modal.trigger>
                                <flux:modal :name="'rf-delete-' . $row->id">
                                    <div class="space-y-4">
                                        <flux:heading size="lg">Delete {{ $row->refund_no }}?</flux:heading>
                                        <flux:text>Cannot be undone. Cascades to attachments.</flux:text>
                                        <div class="flex gap-2 justify-end">
                                            <flux:modal.close><flux:button variant="ghost">Cancel</flux:button></flux:modal.close>
                                            <flux:button variant="danger" wire:click="delete({{ $row->id }})" x-on:click="$flux.modal('rf-delete-{{ $row->id }}').close()">Delete</flux:button>
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
                        <flux:icon.receipt-refund class="mx-auto mb-3 size-8 text-zinc-400" />
                        <div class="font-medium">No refunds yet</div>
                        <flux:text class="mt-1">Refund a customer overpayment or against a sales return.</flux:text>
                    </flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>

    @if ($rows->hasPages())<div class="mt-4"><flux:pagination :paginator="$rows" /></div>@endif
</div>
