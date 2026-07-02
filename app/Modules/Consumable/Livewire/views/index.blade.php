<div>
    <div class="mb-6 flex items-start justify-between gap-4">
        <div>
            <flux:heading size="xl" level="1">Consumables</flux:heading>
            <flux:text class="mt-1">Track consumable usage &amp; loss by category — auto-deducts spare stock on save.</flux:text>
        </div>
        @can('consumable.create')
            <flux:button variant="primary" icon="plus" :href="route('consumable.create')" wire:navigate>New Consumable</flux:button>
        @endcan
    </div>

    <div class="mb-4 flex items-center gap-3 flex-wrap">
        <flux:input wire:model.live.debounce.300ms="search" placeholder="Search by CN no or JC no…" icon="magnifying-glass" clearable class="max-w-md" />
        <flux:select wire:model.live="statusFilter" variant="listbox" class="max-w-44">
            <flux:select.option value="all">All status</flux:select.option>
            @foreach ($statuses as $key => $label)
                <flux:select.option :value="$key">{{ $label }}</flux:select.option>
            @endforeach
        </flux:select>
        <flux:select wire:model.live="categoryFilter" variant="listbox" searchable class="max-w-56">
            <flux:select.option value="all">All categories</flux:select.option>
            @foreach ($this->categories as $c)
                <flux:select.option :value="(string) $c->id">{{ $c->name }}</flux:select.option>
            @endforeach
        </flux:select>
        @if ($search || $statusFilter !== 'all' || $categoryFilter !== 'all')
            <flux:button variant="ghost" size="sm" icon="x-mark" wire:click="clearFilters">Clear</flux:button>
        @endif
    </div>

    <flux:table>
        <flux:table.columns>
            <flux:table.column class="w-28" sortable :sorted="$sortBy === 'consumable_no'" :direction="$sortDirection" wire:click="sort('consumable_no')">No.</flux:table.column>
            <flux:table.column>Category / Job Card</flux:table.column>
            <flux:table.column class="w-40">Loss Reason</flux:table.column>
            <flux:table.column class="w-20 text-center">Lines</flux:table.column>
            <flux:table.column class="w-32 text-right" sortable :sorted="$sortBy === 'total_value'" :direction="$sortDirection" wire:click="sort('total_value')">Value</flux:table.column>
            <flux:table.column class="w-32" sortable :sorted="$sortBy === 'approval_status'" :direction="$sortDirection" wire:click="sort('approval_status')">Approval</flux:table.column>
            <flux:table.column class="w-32" align="end">Actions</flux:table.column>
        </flux:table.columns>
        <flux:table.rows>
            @forelse ($rows as $row)
                <flux:table.row :key="$row->id">
                    <flux:table.cell class="font-mono text-xs">{{ $row->consumable_no ?? '—' }}</flux:table.cell>
                    <flux:table.cell>
                        <div class="text-sm font-medium">{{ $row->category?->name ?? '—' }}</div>
                        <div class="text-xs text-zinc-500 mt-0.5 font-mono">{{ $row->jobCard?->job_card_no ?? '' }}</div>
                    </flux:table.cell>
                    <flux:table.cell class="text-sm">{{ $row->lossReason?->name ?? '—' }}</flux:table.cell>
                    <flux:table.cell class="text-center font-mono text-sm">{{ $row->items_count }}</flux:table.cell>
                    <flux:table.cell class="text-right font-mono text-sm">{{ number_format((float) $row->total_value, 2) }}</flux:table.cell>
                    <flux:table.cell>
                        @php($sc = match ($row->approval_status) {
                            'approved' => 'lime', 'rejected' => 'red', 'on_hold' => 'amber', default => 'sky',
                        })
                        <flux:badge :color="$sc" size="sm">{{ $statuses[$row->approval_status] ?? $row->approval_status }}</flux:badge>
                    </flux:table.cell>
                    <flux:table.cell>
                        <div class="flex items-center justify-end gap-1">
                            @can('consumable.update')
                                <flux:button size="sm" variant="ghost" icon="pencil-square" :href="route('consumable.edit', $row)" wire:navigate>Edit</flux:button>
                            @endcan
                            @can('consumable.delete')
                                <flux:modal.trigger :name="'cn-delete-' . $row->id">
                                    <flux:button size="sm" variant="ghost" icon="trash" />
                                </flux:modal.trigger>
                                <flux:modal :name="'cn-delete-' . $row->id">
                                    <div class="space-y-4">
                                        <flux:heading size="lg">Delete {{ $row->consumable_no }}?</flux:heading>
                                        <flux:text>Cannot be undone. Removes its stock deductions and lines.</flux:text>
                                        <div class="flex gap-2 justify-end">
                                            <flux:modal.close><flux:button variant="ghost">Cancel</flux:button></flux:modal.close>
                                            <flux:button variant="danger" wire:click="delete({{ $row->id }})" x-on:click="$flux.modal('cn-delete-{{ $row->id }}').close()">Delete</flux:button>
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
                        <flux:icon.beaker class="mx-auto mb-3 size-8 text-zinc-400" />
                        <div class="font-medium">No consumables logged yet</div>
                        <flux:text class="mt-1">Record consumable usage / loss to auto-deduct stock.</flux:text>
                    </flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>

    @if ($rows->hasPages())<div class="mt-4"><flux:pagination :paginator="$rows" /></div>@endif
</div>
