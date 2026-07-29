<div>
    <div class="mb-6 flex items-start justify-between gap-4">
        <div>
            <flux:heading size="xl" level="1">Goods Return Note</flux:heading>
            <flux:text class="mt-1">Return excess, incorrect, defective or warranty parts / labour to a vendor.</flux:text>
        </div>
        <div class="flex items-center gap-2">
            @can('goods_return_note.view')
                <flux:button variant="ghost" icon="arrow-down-tray" wire:click="download">Purchase Return Report</flux:button>
            @endcan
            @can('goods_return_note.create')
                <flux:button variant="primary" icon="plus" :href="route('goods-return-note.create')" wire:navigate>New Return</flux:button>
            @endcan
        </div>
    </div>

    <div class="mb-6 grid grid-cols-1 md:grid-cols-2 gap-3">
        <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 p-4">
            <div class="flex items-center gap-2 text-zinc-500"><flux:icon.wrench class="size-4" /><flux:text size="sm">Open Warranty Claims</flux:text></div>
            <div class="mt-1 text-2xl font-semibold tabular-nums">{{ $kpis['open_claims'] }}</div>
        </div>
        <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 p-4">
            <div class="flex items-center gap-2 text-zinc-500"><flux:icon.banknotes class="size-4" /><flux:text size="sm">Recovery Amount Pending</flux:text></div>
            <div class="mt-1 text-2xl font-semibold tabular-nums">₹{{ number_format($kpis['recovery_pending'], 0) }}</div>
        </div>
    </div>

    <div class="mb-4 flex items-center gap-3 flex-wrap">
        <flux:input wire:model.live.debounce.300ms="search" placeholder="Search return no…" icon="magnifying-glass" clearable class="max-w-md" />
        <flux:select wire:model.live="statusFilter" variant="listbox" class="max-w-56">
            <flux:select.option value="all">All status</flux:select.option>
            @foreach ($statuses as $key => $label)<flux:select.option :value="$key">{{ $label }}</flux:select.option>@endforeach
        </flux:select>
        <flux:select wire:model.live="typeFilter" variant="listbox" class="max-w-44">
            <flux:select.option value="all">All types</flux:select.option>
            @foreach ($returnTypes as $key => $label)<flux:select.option :value="$key">{{ $label }}</flux:select.option>@endforeach
        </flux:select>
        @if ($search || $statusFilter !== 'all' || $typeFilter !== 'all' || $vendorFilter !== 'all')
            <flux:button variant="ghost" size="sm" icon="x-mark" wire:click="clearFilters">Clear</flux:button>
        @endif
    </div>

    <flux:table>
        <flux:table.columns>
            <flux:table.column class="w-32" sortable :sorted="$sortBy === 'return_no'" :direction="$sortDirection" wire:click="sort('return_no')">Return</flux:table.column>
            <flux:table.column>Vendor / Vehicle</flux:table.column>
            <flux:table.column class="w-20 text-end">Lines</flux:table.column>
            <flux:table.column class="w-36 text-end" sortable :sorted="$sortBy === 'recovery_amount'" :direction="$sortDirection" wire:click="sort('recovery_amount')">Recovery</flux:table.column>
            <flux:table.column class="w-44">Status</flux:table.column>
            <flux:table.column class="w-28 text-end">Actions</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @forelse ($rows as $row)
                <flux:table.row :key="$row->id">
                    <flux:table.cell class="font-mono text-xs">{{ $row->return_no }}</flux:table.cell>
                    <flux:table.cell class="text-sm">
                        <div>{{ $row->vendor?->name ?? '—' }}</div>
                        <div class="text-xs text-zinc-500 mt-0.5 font-mono">{{ $row->customerVehicle?->registration_no ?? '' }}</div>
                    </flux:table.cell>
                    <flux:table.cell class="text-end font-mono text-sm">{{ $row->items_count }}</flux:table.cell>
                    <flux:table.cell class="text-end font-mono text-sm">{{ $row->recovery_amount !== null ? number_format((float) $row->recovery_amount, 2) : '—' }}</flux:table.cell>
                    <flux:table.cell>
                        @php($sc = match ($row->status) {
                            'fully_accepted', 'cn_adjusted', 'dn_adjusted', 'replacement_adjusted' => 'lime',
                            'partially_accepted', 'rework_in_progress', 'replacement_in_progress' => 'blue',
                            'counter_proposal', 'under_review', 'requested' => 'amber',
                            'rejected', 'cancelled' => 'red',
                            default => 'zinc',
                        })
                        <flux:badge :color="$sc" size="sm">{{ $statuses[$row->status] ?? $row->status }}</flux:badge>
                    </flux:table.cell>
                    <flux:table.cell>
                        <div class="flex items-center justify-end gap-1">
                            @can('goods_return_note.update')
                                <flux:button size="sm" variant="ghost" icon="pencil-square" :href="route('goods-return-note.edit', $row)" wire:navigate>Edit</flux:button>
                            @endcan
                            @can('goods_return_note.delete')
                                <flux:modal.trigger :name="'olr-delete-' . $row->id"><flux:button size="sm" variant="ghost" icon="trash" /></flux:modal.trigger>
                                <flux:modal :name="'olr-delete-' . $row->id">
                                    <div class="space-y-4">
                                        <flux:heading size="lg">Delete {{ $row->return_no }}?</flux:heading>
                                        <flux:text>This cannot be undone. Lines and attachments are removed too.</flux:text>
                                        <div class="flex gap-2 justify-end">
                                            <flux:modal.close><flux:button variant="ghost">Cancel</flux:button></flux:modal.close>
                                            <flux:button variant="danger" wire:click="delete({{ $row->id }})" x-on:click="$flux.modal('olr-delete-{{ $row->id }}').close()">Delete</flux:button>
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
                        <flux:icon.arrow-uturn-left class="mx-auto mb-3 size-8 text-zinc-400" />
                        <div class="font-medium">No goods returns yet</div>
                    </flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>

    @if ($rows->hasPages())<div class="mt-4"><flux:pagination :paginator="$rows" /></div>@endif
</div>
