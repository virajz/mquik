<div>
    <div class="mb-6 flex items-start justify-between gap-4">
        <div>
            <flux:heading size="xl" level="1">Outside Labour Credit / Debit Note</flux:heading>
            <flux:text class="mt-1">CN / DN entry for outside-labour returns and adjustments.</flux:text>
        </div>
        <div class="flex items-center gap-2">
            @can('outside_labour_credit_note.view')
                <flux:button variant="ghost" icon="arrow-down-tray" wire:click="download">Return Register</flux:button>
            @endcan
            @can('outside_labour_credit_note.create')
                <flux:button variant="primary" icon="plus" :href="route('outside-labour-credit-note.create')" wire:navigate>New Note</flux:button>
            @endcan
        </div>
    </div>

    <div class="mb-6 grid grid-cols-1 md:grid-cols-3 gap-3">
        @foreach ([
            ['label' => 'Credit Notes (Posted)', 'value' => $kpis['credit'], 'icon' => 'receipt-refund', 'filter' => 'credit_note'],
            ['label' => 'Debit Notes (Posted)', 'value' => $kpis['debit'], 'icon' => 'receipt-percent', 'filter' => 'debit_note'],
            ['label' => 'Cancelled', 'value' => $kpis['cancelled'], 'icon' => 'x-circle', 'filter' => null],
        ] as $kpi)
            <button type="button" @if ($kpi['filter']) wire:click="$set('typeFilter', '{{ $kpi['filter'] }}')" @else disabled @endif
                class="text-left rounded-lg border border-zinc-200 dark:border-zinc-700 p-4 @if ($kpi['filter']) hover:border-zinc-400 dark:hover:border-zinc-500 transition @endif">
                <div class="flex items-center gap-2 text-zinc-500"><flux:icon :name="$kpi['icon']" class="size-4" /><flux:text size="sm">{{ $kpi['label'] }}</flux:text></div>
                <div class="mt-1 text-2xl font-semibold tabular-nums">{{ $kpi['value'] }}</div>
            </button>
        @endforeach
    </div>

    <div class="mb-4 flex items-center gap-3 flex-wrap">
        <flux:input wire:model.live.debounce.300ms="search" placeholder="Search note no…" icon="magnifying-glass" clearable class="max-w-md" />
        <flux:select wire:model.live="typeFilter" variant="listbox" class="max-w-44">
            <flux:select.option value="all">All types</flux:select.option>
            @foreach ($noteTypes as $key => $label)<flux:select.option :value="$key">{{ $label }}</flux:select.option>@endforeach
        </flux:select>
        <flux:select wire:model.live="statusFilter" variant="listbox" class="max-w-44">
            <flux:select.option value="all">All status</flux:select.option>
            @foreach ($statuses as $key => $label)<flux:select.option :value="$key">{{ $label }}</flux:select.option>@endforeach
        </flux:select>
        @if ($search || $typeFilter !== 'all' || $statusFilter !== 'all' || $vendorFilter !== 'all')
            <flux:button variant="ghost" size="sm" icon="x-mark" wire:click="clearFilters">Clear</flux:button>
        @endif
    </div>

    <flux:table>
        <flux:table.columns>
            <flux:table.column class="w-32" sortable :sorted="$sortBy === 'note_no'" :direction="$sortDirection" wire:click="sort('note_no')">Note</flux:table.column>
            <flux:table.column>Vendor / Return Ref</flux:table.column>
            <flux:table.column class="w-20 text-end">Lines</flux:table.column>
            <flux:table.column class="w-32 text-end" sortable :sorted="$sortBy === 'amount'" :direction="$sortDirection" wire:click="sort('amount')">Amount</flux:table.column>
            <flux:table.column class="w-28">Status</flux:table.column>
            <flux:table.column class="w-28 text-end">Actions</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @forelse ($rows as $row)
                <flux:table.row :key="$row->id">
                    <flux:table.cell class="font-mono text-xs">{{ $row->note_no }}
                        <flux:badge size="sm" :color="$row->note_type === 'debit_note' ? 'orange' : 'sky'" class="ml-1">{{ $noteTypes[$row->note_type] ?? $row->note_type }}</flux:badge>
                    </flux:table.cell>
                    <flux:table.cell class="text-sm">
                        <div>{{ $row->vendor?->name ?? '—' }}</div>
                        <div class="text-xs text-zinc-500 mt-0.5 font-mono">{{ $row->return?->return_no ?? '' }}</div>
                    </flux:table.cell>
                    <flux:table.cell class="text-end font-mono text-sm">{{ $row->items_count }}</flux:table.cell>
                    <flux:table.cell class="text-end font-mono text-sm">{{ $row->amount !== null ? number_format((float) $row->amount, 2) : '—' }}</flux:table.cell>
                    <flux:table.cell>
                        <flux:badge :color="$row->status === 'cancelled' ? 'red' : 'lime'" size="sm">{{ $statuses[$row->status] ?? $row->status }}</flux:badge>
                    </flux:table.cell>
                    <flux:table.cell>
                        <div class="flex items-center justify-end gap-1">
                            @can('outside_labour_credit_note.update')
                                <flux:button size="sm" variant="ghost" icon="pencil-square" :href="route('outside-labour-credit-note.edit', $row)" wire:navigate>Edit</flux:button>
                            @endcan
                            @can('outside_labour_credit_note.delete')
                                <flux:modal.trigger :name="'olcn-delete-' . $row->id"><flux:button size="sm" variant="ghost" icon="trash" /></flux:modal.trigger>
                                <flux:modal :name="'olcn-delete-' . $row->id">
                                    <div class="space-y-4">
                                        <flux:heading size="lg">Delete {{ $row->note_no }}?</flux:heading>
                                        <flux:text>This cannot be undone. Lines and attachments are removed too.</flux:text>
                                        <div class="flex gap-2 justify-end">
                                            <flux:modal.close><flux:button variant="ghost">Cancel</flux:button></flux:modal.close>
                                            <flux:button variant="danger" wire:click="delete({{ $row->id }})" x-on:click="$flux.modal('olcn-delete-{{ $row->id }}').close()">Delete</flux:button>
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
                        <div class="font-medium">No credit / debit notes yet</div>
                    </flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>

    @if ($rows->hasPages())<div class="mt-4"><flux:pagination :paginator="$rows" /></div>@endif
</div>
