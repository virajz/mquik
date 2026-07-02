<div>
    <div class="mb-6 flex items-start justify-between gap-4">
        <div>
            <flux:heading size="xl" level="1">Goods Returns</flux:heading>
            <flux:text class="mt-1">Vendor credit / debit notes for returned parts — reduces stock (RTS settlement).</flux:text>
        </div>
        @can('goods_return.create')
            <flux:button variant="primary" icon="plus" :href="route('goods-return.create')" wire:navigate>New Return</flux:button>
        @endcan
    </div>

    <div class="mb-4 flex items-center gap-3 flex-wrap">
        <flux:input wire:model.live.debounce.300ms="search" placeholder="Search by GR no, GRN ref or vendor…" icon="magnifying-glass" clearable class="max-w-md" />
        <flux:select wire:model.live="typeFilter" variant="listbox" class="max-w-44">
            <flux:select.option value="all">All types</flux:select.option>
            @foreach ($documentTypes as $key => $label)
                <flux:select.option :value="$key">{{ $label }}</flux:select.option>
            @endforeach
        </flux:select>
    </div>

    <flux:table>
        <flux:table.columns>
            <flux:table.column class="w-28" sortable :sorted="$sortBy === 'return_no'" :direction="$sortDirection" wire:click="sort('return_no')">No.</flux:table.column>
            <flux:table.column>Vendor / Reason</flux:table.column>
            <flux:table.column class="w-28">Type</flux:table.column>
            <flux:table.column class="w-20 text-center">Lines</flux:table.column>
            <flux:table.column class="w-32 text-right" sortable :sorted="$sortBy === 'grand_total'" :direction="$sortDirection" wire:click="sort('grand_total')">Total</flux:table.column>
            <flux:table.column class="w-32" align="end">Actions</flux:table.column>
        </flux:table.columns>
        <flux:table.rows>
            @forelse ($rows as $row)
                <flux:table.row :key="$row->id">
                    <flux:table.cell class="font-mono text-xs">{{ $row->return_no ?? '—' }}</flux:table.cell>
                    <flux:table.cell>
                        <div class="text-sm font-medium">{{ $row->vendor?->name ?? '—' }}</div>
                        <div class="text-xs text-zinc-500 mt-0.5">{{ $row->creditNoteReason?->name ?? '' }}</div>
                    </flux:table.cell>
                    <flux:table.cell>
                        <flux:badge size="sm" :color="$row->document_type === 'debit_note' ? 'orange' : 'sky'">{{ $documentTypes[$row->document_type] ?? $row->document_type }}</flux:badge>
                    </flux:table.cell>
                    <flux:table.cell class="text-center font-mono text-sm">{{ $row->items_count }}</flux:table.cell>
                    <flux:table.cell class="text-right font-mono text-sm">{{ number_format((float) $row->grand_total, 2) }}</flux:table.cell>
                    <flux:table.cell>
                        <div class="flex items-center justify-end gap-1">
                            @can('goods_return.update')
                                <flux:button size="sm" variant="ghost" icon="pencil-square" :href="route('goods-return.edit', $row)" wire:navigate>Edit</flux:button>
                            @endcan
                            @can('goods_return.delete')
                                <flux:modal.trigger :name="'gr-delete-' . $row->id">
                                    <flux:button size="sm" variant="ghost" icon="trash" />
                                </flux:modal.trigger>
                                <flux:modal :name="'gr-delete-' . $row->id">
                                    <div class="space-y-4">
                                        <flux:heading size="lg">Delete {{ $row->return_no }}?</flux:heading>
                                        <flux:text>Cannot be undone. Removes its stock entries, lines and attachments.</flux:text>
                                        <div class="flex gap-2 justify-end">
                                            <flux:modal.close><flux:button variant="ghost">Cancel</flux:button></flux:modal.close>
                                            <flux:button variant="danger" wire:click="delete({{ $row->id }})" x-on:click="$flux.modal('gr-delete-{{ $row->id }}').close()">Delete</flux:button>
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
                        <flux:icon.receipt-refund class="mx-auto mb-3 size-8 text-zinc-400" />
                        <div class="font-medium">No goods returns yet</div>
                        <flux:text class="mt-1">Raise a credit note for parts returned to a vendor.</flux:text>
                    </flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>

    @if ($rows->hasPages())<div class="mt-4"><flux:pagination :paginator="$rows" /></div>@endif
</div>
