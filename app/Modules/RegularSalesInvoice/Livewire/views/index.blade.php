<div>
    <div class="mb-6 flex items-start justify-between gap-4">
        <div>
            <flux:heading size="xl" level="1">Regular Sales Invoices</flux:heading>
            <flux:text class="mt-1">Final regular / insurance invoices — spares &amp; labour, tax, gross margin, warranty and payment status.</flux:text>
        </div>
        @can('regular_sales_invoice.create')
            <flux:button variant="primary" icon="plus" :href="route('regular-sales-invoice.create')" wire:navigate>New Invoice</flux:button>
        @endcan
    </div>

    <div class="mb-4 flex items-center gap-3 flex-wrap">
        <flux:input wire:model.live.debounce.300ms="search" placeholder="Search by invoice no, policy or reg no…" icon="magnifying-glass" clearable class="max-w-md" />
        <flux:select wire:model.live="statusFilter" variant="listbox" class="max-w-44">
            <flux:select.option value="all">All status</flux:select.option>
            @foreach ($statuses as $key => $label)
                <flux:select.option :value="$key">{{ $label }}</flux:select.option>
            @endforeach
        </flux:select>
        <flux:select wire:model.live="paymentFilter" variant="listbox" class="max-w-44">
            <flux:select.option value="all">All payments</flux:select.option>
            @foreach ($paymentStatuses as $key => $label)
                <flux:select.option :value="$key">{{ $label }}</flux:select.option>
            @endforeach
        </flux:select>
        @if ($search || $statusFilter !== 'all' || $paymentFilter !== 'all')
            <flux:button variant="ghost" size="sm" icon="x-mark" wire:click="clearFilters">Clear</flux:button>
        @endif
    </div>

    <flux:table>
        <flux:table.columns>
            <flux:table.column class="w-40" sortable :sorted="$sortBy === 'invoice_no'" :direction="$sortDirection" wire:click="sort('invoice_no')">Invoice No.</flux:table.column>
            <flux:table.column>Customer / Vehicle</flux:table.column>
            <flux:table.column class="w-16 text-center">Lines</flux:table.column>
            <flux:table.column class="w-32 text-right" sortable :sorted="$sortBy === 'grand_total'" :direction="$sortDirection" wire:click="sort('grand_total')">Total</flux:table.column>
            <flux:table.column class="w-28 text-right" sortable :sorted="$sortBy === 'profit_total'" :direction="$sortDirection" wire:click="sort('profit_total')">Profit</flux:table.column>
            <flux:table.column class="w-32" sortable :sorted="$sortBy === 'payment_status'" :direction="$sortDirection" wire:click="sort('payment_status')">Payment</flux:table.column>
            <flux:table.column class="w-32" sortable :sorted="$sortBy === 'status'" :direction="$sortDirection" wire:click="sort('status')">Status</flux:table.column>
            <flux:table.column class="w-32" align="end">Actions</flux:table.column>
        </flux:table.columns>
        <flux:table.rows>
            @forelse ($rows as $row)
                <flux:table.row :key="$row->id">
                    <flux:table.cell class="font-mono text-xs">{{ $row->invoice_no ?? '—' }}</flux:table.cell>
                    <flux:table.cell>
                        <div class="text-sm font-medium">{{ trim($row->customer?->first_name.' '.($row->customer?->last_name ?? '')) }}</div>
                        <div class="text-xs text-zinc-500 mt-0.5 flex items-center gap-2">
                            @if ($row->customerVehicle)<span class="font-mono">{{ $row->customerVehicle->registration_no }}</span>@endif
                            @if ($row->insuranceCompany)<span>· {{ $row->insuranceCompany->name }}</span>@endif
                        </div>
                    </flux:table.cell>
                    <flux:table.cell class="text-center font-mono text-sm">{{ $row->items_count }}</flux:table.cell>
                    <flux:table.cell class="text-right font-mono text-sm">{{ number_format((float) $row->grand_total, 2) }}</flux:table.cell>
                    <flux:table.cell class="text-right font-mono text-sm {{ (float) $row->profit_total < 0 ? 'text-rose-600 dark:text-rose-400' : 'text-emerald-600 dark:text-emerald-400' }}">
                        {{ number_format((float) $row->profit_total, 2) }}
                    </flux:table.cell>
                    <flux:table.cell>
                        @php($pc = match ($row->payment_status) {
                            'fully_paid' => 'lime', 'partially_paid' => 'amber', 'refunded' => 'purple', default => 'zinc',
                        })
                        <flux:badge :color="$pc" size="sm">{{ $paymentStatuses[$row->payment_status] ?? $row->payment_status }}</flux:badge>
                    </flux:table.cell>
                    <flux:table.cell>
                        @php($sc = match ($row->status) {
                            'finalized' => 'green', 'cancelled' => 'zinc', 'credit_note' => 'red', default => 'sky',
                        })
                        <flux:badge :color="$sc" size="sm">{{ $statuses[$row->status] ?? $row->status }}</flux:badge>
                    </flux:table.cell>
                    <flux:table.cell>
                        <div class="flex items-center justify-end gap-1">
                            @can('regular_sales_invoice.update')
                                <flux:button size="sm" variant="ghost" icon="pencil-square" :href="route('regular-sales-invoice.edit', $row)" wire:navigate>Edit</flux:button>
                            @endcan
                            @can('regular_sales_invoice.delete')
                                <flux:modal.trigger :name="'rsi-delete-' . $row->id">
                                    <flux:button size="sm" variant="ghost" icon="trash" />
                                </flux:modal.trigger>
                                <flux:modal :name="'rsi-delete-' . $row->id">
                                    <div class="space-y-4">
                                        <flux:heading size="lg">Delete {{ $row->invoice_no }}?</flux:heading>
                                        <flux:text>Cannot be undone. Cascades to lines, deductions and attachments.</flux:text>
                                        <div class="flex gap-2 justify-end">
                                            <flux:modal.close><flux:button variant="ghost">Cancel</flux:button></flux:modal.close>
                                            <flux:button variant="danger" wire:click="delete({{ $row->id }})" x-on:click="$flux.modal('rsi-delete-{{ $row->id }}').close()">Delete</flux:button>
                                        </div>
                                    </div>
                                </flux:modal>
                            @endcan
                        </div>
                    </flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="8" class="text-center text-zinc-500 py-12">
                        <flux:icon.receipt-percent class="mx-auto mb-3 size-8 text-zinc-400" />
                        <div class="font-medium">No invoices yet</div>
                        <flux:text class="mt-1">Generate one from a proforma, estimate or job card.</flux:text>
                    </flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>

    @if ($rows->hasPages())<div class="mt-4"><flux:pagination :paginator="$rows" /></div>@endif
</div>
