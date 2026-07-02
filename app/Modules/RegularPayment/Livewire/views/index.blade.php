<div>
    <div class="mb-6 flex items-start justify-between gap-4">
        <div>
            <flux:heading size="xl" level="1">Regular Payments</flux:heading>
            <flux:text class="mt-1">Payments to vendors — payment mode, cheque tracking, hold &amp; cancellation, against purchase invoices.</flux:text>
        </div>
        @can('regular_payment.create')
            <flux:button variant="primary" icon="plus" :href="route('regular-payment.create')" wire:navigate>New Payment</flux:button>
        @endcan
    </div>

    <div class="mb-4 flex items-center gap-3 flex-wrap">
        <flux:input wire:model.live.debounce.300ms="search" placeholder="Search by payment no, ref, cheque or vendor…" icon="magnifying-glass" clearable class="max-w-md" />
        <flux:select wire:model.live="statusFilter" variant="listbox" class="max-w-40">
            <flux:select.option value="all">All status</flux:select.option>
            @foreach ($statuses as $key => $label)
                <flux:select.option :value="$key">{{ $label }}</flux:select.option>
            @endforeach
        </flux:select>
        <flux:input type="month" wire:model.live="monthFilter" class="max-w-44" />
        @if ($search || $statusFilter !== 'all' || $monthFilter !== '')
            <flux:button variant="ghost" size="sm" icon="x-mark" wire:click="clearFilters">Clear</flux:button>
        @endif
    </div>

    <flux:table>
        <flux:table.columns>
            <flux:table.column class="w-44" sortable :sorted="$sortBy === 'payment_no'" :direction="$sortDirection" wire:click="sort('payment_no')">Payment No.</flux:table.column>
            <flux:table.column>Vendor</flux:table.column>
            <flux:table.column class="w-32">Mode</flux:table.column>
            <flux:table.column class="w-32 text-right" sortable :sorted="$sortBy === 'amount'" :direction="$sortDirection" wire:click="sort('amount')">Amount</flux:table.column>
            <flux:table.column class="w-32">Cheque</flux:table.column>
            <flux:table.column class="w-28" sortable :sorted="$sortBy === 'status'" :direction="$sortDirection" wire:click="sort('status')">Status</flux:table.column>
            <flux:table.column class="w-32" align="end">Actions</flux:table.column>
        </flux:table.columns>
        <flux:table.rows>
            @forelse ($rows as $row)
                <flux:table.row :key="$row->id">
                    <flux:table.cell class="font-mono text-xs">{{ $row->payment_no ?? '—' }}</flux:table.cell>
                    <flux:table.cell class="text-sm font-medium">{{ $row->vendor?->name ?? '—' }}</flux:table.cell>
                    <flux:table.cell class="text-sm text-zinc-500">{{ $row->paymentMode?->name ?? '—' }}</flux:table.cell>
                    <flux:table.cell class="text-right font-mono text-sm">{{ number_format((float) $row->amount, 2) }}</flux:table.cell>
                    <flux:table.cell>
                        @if ($row->cheque_status)
                            @php($cc = match ($row->cheque_status) {
                                'cleared' => 'lime', 'bounced' => 'red', 'cancelled' => 'zinc', default => 'sky',
                            })
                            <flux:badge :color="$cc" size="sm">{{ $row->cheque_status === 'bounced' ? 'Bounced' : ucfirst($row->cheque_status) }}</flux:badge>
                        @else
                            <span class="text-zinc-400 text-xs">—</span>
                        @endif
                    </flux:table.cell>
                    <flux:table.cell>
                        @php($sc = match ($row->status) {
                            'paid' => 'green', 'on_hold' => 'amber', 'cancelled' => 'red', default => 'sky',
                        })
                        <flux:badge :color="$sc" size="sm">{{ $statuses[$row->status] ?? $row->status }}</flux:badge>
                    </flux:table.cell>
                    <flux:table.cell>
                        <div class="flex items-center justify-end gap-1">
                            @can('regular_payment.update')
                                <flux:button size="sm" variant="ghost" icon="pencil-square" :href="route('regular-payment.edit', $row)" wire:navigate>Edit</flux:button>
                            @endcan
                            @can('regular_payment.delete')
                                <flux:modal.trigger :name="'pay-delete-' . $row->id">
                                    <flux:button size="sm" variant="ghost" icon="trash" />
                                </flux:modal.trigger>
                                <flux:modal :name="'pay-delete-' . $row->id">
                                    <div class="space-y-4">
                                        <flux:heading size="lg">Delete {{ $row->payment_no }}?</flux:heading>
                                        <flux:text>Cannot be undone. Cascades to attachments.</flux:text>
                                        <div class="flex gap-2 justify-end">
                                            <flux:modal.close><flux:button variant="ghost">Cancel</flux:button></flux:modal.close>
                                            <flux:button variant="danger" wire:click="delete({{ $row->id }})" x-on:click="$flux.modal('pay-delete-{{ $row->id }}').close()">Delete</flux:button>
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
                        <div class="font-medium">No payments yet</div>
                        <flux:text class="mt-1">Record a payment to a vendor against a purchase invoice.</flux:text>
                    </flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>

    @if ($rows->hasPages())<div class="mt-4"><flux:pagination :paginator="$rows" /></div>@endif
</div>
