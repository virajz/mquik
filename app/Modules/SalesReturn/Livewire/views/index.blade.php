<div>
    <div class="mb-6 flex items-start justify-between gap-4">
        <div>
            <flux:heading size="xl" level="1">Sales Returns</flux:heading>
            <flux:text class="mt-1">Returns across regular / insurance / counter sales — reason, refund status, and stock restore.</flux:text>
        </div>
        @can('sales_return.create')
            <flux:button variant="primary" icon="plus" :href="route('sales-return.create')" wire:navigate>New Return</flux:button>
        @endcan
    </div>

    <div class="mb-4 flex items-center gap-3 flex-wrap">
        <flux:input wire:model.live.debounce.300ms="search" placeholder="Search by return no or customer…" icon="magnifying-glass" clearable class="max-w-md" />
        <flux:select wire:model.live="typeFilter" variant="listbox" class="max-w-40">
            <flux:select.option value="all">All types</flux:select.option>
            @foreach ($returnTypes as $key => $label)
                <flux:select.option :value="$key">{{ $label }}</flux:select.option>
            @endforeach
        </flux:select>
        <flux:select wire:model.live="statusFilter" variant="listbox" class="max-w-40">
            <flux:select.option value="all">All status</flux:select.option>
            @foreach ($statuses as $key => $label)
                <flux:select.option :value="$key">{{ $label }}</flux:select.option>
            @endforeach
        </flux:select>
        <flux:select wire:model.live="refundFilter" variant="listbox" class="max-w-48">
            <flux:select.option value="all">All refunds</flux:select.option>
            @foreach ($refundStatuses as $key => $label)
                <flux:select.option :value="$key">{{ $label }}</flux:select.option>
            @endforeach
        </flux:select>
        @if ($search || $typeFilter !== 'all' || $statusFilter !== 'all' || $refundFilter !== 'all')
            <flux:button variant="ghost" size="sm" icon="x-mark" wire:click="clearFilters">Clear</flux:button>
        @endif
    </div>

    <flux:table>
        <flux:table.columns>
            <flux:table.column class="w-44" sortable :sorted="$sortBy === 'return_no'" :direction="$sortDirection" wire:click="sort('return_no')">Return No.</flux:table.column>
            <flux:table.column>Customer / Reason</flux:table.column>
            <flux:table.column class="w-24" sortable :sorted="$sortBy === 'return_type'" :direction="$sortDirection" wire:click="sort('return_type')">Type</flux:table.column>
            <flux:table.column class="w-16 text-center">Lines</flux:table.column>
            <flux:table.column class="w-32 text-right" sortable :sorted="$sortBy === 'grand_total'" :direction="$sortDirection" wire:click="sort('grand_total')">Value</flux:table.column>
            <flux:table.column class="w-36" sortable :sorted="$sortBy === 'refund_status'" :direction="$sortDirection" wire:click="sort('refund_status')">Refund</flux:table.column>
            <flux:table.column class="w-28" sortable :sorted="$sortBy === 'status'" :direction="$sortDirection" wire:click="sort('status')">Status</flux:table.column>
            <flux:table.column class="w-32" align="end">Actions</flux:table.column>
        </flux:table.columns>
        <flux:table.rows>
            @forelse ($rows as $row)
                <flux:table.row :key="$row->id">
                    <flux:table.cell class="font-mono text-xs">{{ $row->return_no ?? '—' }}</flux:table.cell>
                    <flux:table.cell>
                        <div class="text-sm font-medium">{{ trim($row->customer?->first_name.' '.($row->customer?->last_name ?? '')) }}</div>
                        @if ($row->returnReason)<div class="text-xs text-zinc-500 mt-0.5">{{ $row->returnReason->name }}</div>@endif
                    </flux:table.cell>
                    <flux:table.cell><flux:badge size="sm" color="zinc">{{ $returnTypes[$row->return_type] ?? $row->return_type }}</flux:badge></flux:table.cell>
                    <flux:table.cell class="text-center font-mono text-sm">{{ $row->items_count }}</flux:table.cell>
                    <flux:table.cell class="text-right font-mono text-sm">{{ number_format((float) $row->grand_total, 2) }}</flux:table.cell>
                    <flux:table.cell>
                        @php($rc = match ($row->refund_status) {
                            'fully_refunded' => 'lime', 'partially_refunded' => 'amber', default => 'zinc',
                        })
                        <flux:badge :color="$rc" size="sm">{{ $refundStatuses[$row->refund_status] ?? $row->refund_status }}</flux:badge>
                    </flux:table.cell>
                    <flux:table.cell>
                        @php($sc = match ($row->status) {
                            'finalized' => 'green', 'cancelled' => 'red', default => 'sky',
                        })
                        <flux:badge :color="$sc" size="sm">{{ $statuses[$row->status] ?? $row->status }}</flux:badge>
                    </flux:table.cell>
                    <flux:table.cell>
                        <div class="flex items-center justify-end gap-1">
                            @can('sales_return.update')
                                <flux:button size="sm" variant="ghost" icon="pencil-square" :href="route('sales-return.edit', $row)" wire:navigate>Edit</flux:button>
                            @endcan
                            @can('sales_return.delete')
                                <flux:modal.trigger :name="'sr-delete-' . $row->id">
                                    <flux:button size="sm" variant="ghost" icon="trash" />
                                </flux:modal.trigger>
                                <flux:modal :name="'sr-delete-' . $row->id">
                                    <div class="space-y-4">
                                        <flux:heading size="lg">Delete {{ $row->return_no }}?</flux:heading>
                                        <flux:text>Cannot be undone. Cascades to line items and reverses its stock movement.</flux:text>
                                        <div class="flex gap-2 justify-end">
                                            <flux:modal.close><flux:button variant="ghost">Cancel</flux:button></flux:modal.close>
                                            <flux:button variant="danger" wire:click="delete({{ $row->id }})" x-on:click="$flux.modal('sr-delete-{{ $row->id }}').close()">Delete</flux:button>
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
                        <flux:icon.arrow-uturn-left class="mx-auto mb-3 size-8 text-zinc-400" />
                        <div class="font-medium">No sales returns yet</div>
                        <flux:text class="mt-1">Create one from a regular, insurance or counter sales invoice.</flux:text>
                    </flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>

    @if ($rows->hasPages())<div class="mt-4"><flux:pagination :paginator="$rows" /></div>@endif
</div>
