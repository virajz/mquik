<div>
    <div class="mb-6 flex items-start justify-between gap-4">
        <div>
            <flux:heading size="xl" level="1">Challan Entries</flux:heading>
            <flux:text class="mt-1">Inward part entry via challan — material condition, charges, invoice &amp; inventory status.</flux:text>
        </div>
        @can('challan_entry.create')
            <flux:button variant="primary" icon="plus" :href="route('challan-entry.create')" wire:navigate>New Challan</flux:button>
        @endcan
    </div>

    <div class="mb-4 flex items-center gap-3 flex-wrap">
        <flux:input wire:model.live.debounce.300ms="search" placeholder="Search by challan no, PO or vendor…" icon="magnifying-glass" clearable class="max-w-md" />
        <flux:select wire:model.live="statusFilter" variant="listbox" class="max-w-52">
            <flux:select.option value="all">All inventory status</flux:select.option>
            @foreach ($statuses as $key => $label)
                <flux:select.option :value="$key">{{ $label }}</flux:select.option>
            @endforeach
        </flux:select>
        <flux:select wire:model.live="typeFilter" variant="listbox" class="max-w-52">
            <flux:select.option value="all">All purchase types</flux:select.option>
            @foreach ($types as $key => $label)
                <flux:select.option :value="$key">{{ $label }}</flux:select.option>
            @endforeach
        </flux:select>
        @if ($search || $statusFilter !== 'all' || $typeFilter !== 'all')
            <flux:button variant="ghost" size="sm" icon="x-mark" wire:click="clearFilters">Clear</flux:button>
        @endif
    </div>

    <flux:table>
        <flux:table.columns>
            <flux:table.column class="w-28" sortable :sorted="$sortBy === 'challan_no'" :direction="$sortDirection" wire:click="sort('challan_no')">No.</flux:table.column>
            <flux:table.column>Vendor / Job Card</flux:table.column>
            <flux:table.column class="w-44">Purchase Type</flux:table.column>
            <flux:table.column class="w-24 text-center">Lines</flux:table.column>
            <flux:table.column class="w-32 text-right" sortable :sorted="$sortBy === 'grand_total'" :direction="$sortDirection" wire:click="sort('grand_total')">Total</flux:table.column>
            <flux:table.column class="w-40">Inventory</flux:table.column>
            <flux:table.column class="w-32" align="end">Actions</flux:table.column>
        </flux:table.columns>
        <flux:table.rows>
            @forelse ($rows as $row)
                <flux:table.row :key="$row->id">
                    <flux:table.cell class="font-mono text-xs">{{ $row->challan_no ?? '—' }}</flux:table.cell>
                    <flux:table.cell>
                        <div class="text-sm font-medium">{{ $row->vendor?->name ?? '—' }}</div>
                        <div class="text-xs text-zinc-500 mt-0.5 font-mono">{{ $row->jobCard?->job_card_no ?? '' }}</div>
                    </flux:table.cell>
                    <flux:table.cell class="text-sm">{{ $types[$row->purchase_type] ?? $row->purchase_type }}</flux:table.cell>
                    <flux:table.cell class="text-center font-mono text-sm">{{ $row->items_count }}</flux:table.cell>
                    <flux:table.cell class="text-right font-mono text-sm">{{ number_format((float) $row->grand_total, 2) }}</flux:table.cell>
                    <flux:table.cell>
                        @php($sc = match ($row->inventory_status) {
                            'fully_received' => 'lime', 'partially_received','spares_received' => 'teal',
                            'spares_in_transit','spares_dispatched' => 'sky', 'spares_not_received' => 'amber',
                            'escalated' => 'orange', 'cancelled' => 'red', 'closed' => 'green', default => 'zinc',
                        })
                        <flux:badge :color="$sc" size="sm">{{ $statuses[$row->inventory_status] ?? $row->inventory_status }}</flux:badge>
                    </flux:table.cell>
                    <flux:table.cell>
                        <div class="flex items-center justify-end gap-1">
                            @can('challan_entry.update')
                                <flux:button size="sm" variant="ghost" icon="pencil-square" :href="route('challan-entry.edit', $row)" wire:navigate>Edit</flux:button>
                            @endcan
                            @can('challan_entry.delete')
                                <flux:modal.trigger :name="'ch-delete-' . $row->id">
                                    <flux:button size="sm" variant="ghost" icon="trash" />
                                </flux:modal.trigger>
                                <flux:modal :name="'ch-delete-' . $row->id">
                                    <div class="space-y-4">
                                        <flux:heading size="lg">Delete {{ $row->challan_no }}?</flux:heading>
                                        <flux:text>Cannot be undone. Cascades to lines, charges and attachments.</flux:text>
                                        <div class="flex gap-2 justify-end">
                                            <flux:modal.close><flux:button variant="ghost">Cancel</flux:button></flux:modal.close>
                                            <flux:button variant="danger" wire:click="delete({{ $row->id }})" x-on:click="$flux.modal('ch-delete-{{ $row->id }}').close()">Delete</flux:button>
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
                        <flux:icon.document-text class="mx-auto mb-3 size-8 text-zinc-400" />
                        <div class="font-medium">No challans yet</div>
                        <flux:text class="mt-1">Record inward parts received against a delivery challan.</flux:text>
                    </flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>

    @if ($rows->hasPages())<div class="mt-4"><flux:pagination :paginator="$rows" /></div>@endif
</div>
