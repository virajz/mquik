<div>
    <div class="mb-6 flex items-start justify-between gap-4">
        <div>
            <flux:heading size="xl" level="1">Outside Labour Entries</flux:heading>
            <flux:text class="mt-1">Outside vendor work (parts + labour) recorded against a physical invoice.</flux:text>
        </div>
        @can('outside_labour_entry.create')
            <flux:button variant="primary" icon="plus" :href="route('outside-labour-entry.create')" wire:navigate>New Entry</flux:button>
        @endcan
    </div>

    <div class="mb-4">
        <flux:input wire:model.live.debounce.300ms="search" placeholder="Search by entry no, invoice or vendor…" icon="magnifying-glass" clearable class="max-w-md" />
    </div>

    <flux:table>
        <flux:table.columns>
            <flux:table.column class="w-28" sortable :sorted="$sortBy === 'entry_no'" :direction="$sortDirection" wire:click="sort('entry_no')">No.</flux:table.column>
            <flux:table.column>Vendor / Job Card</flux:table.column>
            <flux:table.column class="w-24 text-center">Lines</flux:table.column>
            <flux:table.column class="w-32 text-right" sortable :sorted="$sortBy === 'grand_total'" :direction="$sortDirection" wire:click="sort('grand_total')">Total</flux:table.column>
            <flux:table.column class="w-32" align="end">Actions</flux:table.column>
        </flux:table.columns>
        <flux:table.rows>
            @forelse ($rows as $row)
                <flux:table.row :key="$row->id">
                    <flux:table.cell class="font-mono text-xs">{{ $row->entry_no ?? '—' }}</flux:table.cell>
                    <flux:table.cell>
                        <div class="text-sm font-medium">{{ $row->vendor?->name ?? '—' }}</div>
                        <div class="text-xs text-zinc-500 mt-0.5 font-mono">{{ $row->jobCard?->job_card_no ?? '' }}</div>
                    </flux:table.cell>
                    <flux:table.cell class="text-center font-mono text-sm">{{ $row->items_count }}</flux:table.cell>
                    <flux:table.cell class="text-right font-mono text-sm">{{ number_format((float) $row->grand_total, 2) }}</flux:table.cell>
                    <flux:table.cell>
                        <div class="flex items-center justify-end gap-1">
                            @can('outside_labour_entry.update')
                                <flux:button size="sm" variant="ghost" icon="pencil-square" :href="route('outside-labour-entry.edit', $row)" wire:navigate>Edit</flux:button>
                            @endcan
                            @can('outside_labour_entry.delete')
                                <flux:modal.trigger :name="'ole-delete-' . $row->id">
                                    <flux:button size="sm" variant="ghost" icon="trash" />
                                </flux:modal.trigger>
                                <flux:modal :name="'ole-delete-' . $row->id">
                                    <div class="space-y-4">
                                        <flux:heading size="lg">Delete {{ $row->entry_no }}?</flux:heading>
                                        <flux:text>Cannot be undone. Cascades to lines and attachments.</flux:text>
                                        <div class="flex gap-2 justify-end">
                                            <flux:modal.close><flux:button variant="ghost">Cancel</flux:button></flux:modal.close>
                                            <flux:button variant="danger" wire:click="delete({{ $row->id }})" x-on:click="$flux.modal('ole-delete-{{ $row->id }}').close()">Delete</flux:button>
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
                        <flux:icon.wrench class="mx-auto mb-3 size-8 text-zinc-400" />
                        <div class="font-medium">No outside labour entries yet</div>
                        <flux:text class="mt-1">Record outside vendor work against its invoice.</flux:text>
                    </flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>

    @if ($rows->hasPages())<div class="mt-4"><flux:pagination :paginator="$rows" /></div>@endif
</div>
