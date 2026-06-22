<div>
    <div class="mb-6 flex items-start justify-between gap-4">
        <div>
            <flux:heading size="xl" level="1">Internal Part Orders</flux:heading>
            <flux:text class="mt-1">Advisor/floor requests parts from the store — request, approval, issue and returns (IPO/IPR).</flux:text>
        </div>
        @can('internal_part_order.create')
            <flux:button variant="primary" icon="plus" :href="route('internal-part-order.create')" wire:navigate>New IPO</flux:button>
        @endcan
    </div>

    <div class="mb-4 flex items-center gap-3 flex-wrap">
        <flux:input wire:model.live.debounce.300ms="search" placeholder="Search by IPO no or JC no…" icon="magnifying-glass" clearable class="max-w-md" />
        <flux:select wire:model.live="statusFilter" variant="listbox" class="max-w-44">
            <flux:select.option value="all">All status</flux:select.option>
            @foreach ($statuses as $key => $label)
                <flux:select.option :value="$key">{{ $label }}</flux:select.option>
            @endforeach
        </flux:select>
        <flux:select wire:model.live="priorityFilter" variant="listbox" class="max-w-40">
            <flux:select.option value="all">All priority</flux:select.option>
            @foreach ($priorities as $key => $label)
                <flux:select.option :value="$key">{{ $label }}</flux:select.option>
            @endforeach
        </flux:select>
        <flux:select wire:model.live="typeFilter" variant="listbox" class="max-w-52">
            <flux:select.option value="all">All types</flux:select.option>
            @foreach ($types as $key => $label)
                <flux:select.option :value="$key">{{ $label }}</flux:select.option>
            @endforeach
        </flux:select>
        @if ($search || $statusFilter !== 'all' || $priorityFilter !== 'all' || $typeFilter !== 'all')
            <flux:button variant="ghost" size="sm" icon="x-mark" wire:click="clearFilters">Clear</flux:button>
        @endif
    </div>

    <flux:table>
        <flux:table.columns>
            <flux:table.column class="w-28" sortable :sorted="$sortBy === 'order_no'" :direction="$sortDirection" wire:click="sort('order_no')">No.</flux:table.column>
            <flux:table.column>Job Card / Requested By</flux:table.column>
            <flux:table.column class="w-44">Type</flux:table.column>
            <flux:table.column class="w-24 text-center">Lines</flux:table.column>
            <flux:table.column class="w-28" sortable :sorted="$sortBy === 'order_priority'" :direction="$sortDirection" wire:click="sort('order_priority')">Priority</flux:table.column>
            <flux:table.column class="w-36" sortable :sorted="$sortBy === 'status'" :direction="$sortDirection" wire:click="sort('status')">Status</flux:table.column>
            <flux:table.column class="w-32" align="end">Actions</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @forelse ($rows as $row)
                <flux:table.row :key="$row->id">
                    <flux:table.cell class="font-mono text-xs">{{ $row->order_no ?? '—' }}</flux:table.cell>
                    <flux:table.cell>
                        <div class="font-mono font-medium text-sm">{{ $row->jobCard?->job_card_no ?? '— no job card —' }}</div>
                        <div class="text-xs text-zinc-500 mt-0.5">{{ $row->requestedBy?->name ?? '' }}</div>
                    </flux:table.cell>
                    <flux:table.cell class="text-sm">{{ $types[$row->ipo_type] ?? $row->ipo_type }}</flux:table.cell>
                    <flux:table.cell class="text-center font-mono text-sm">{{ $row->items_count }}</flux:table.cell>
                    <flux:table.cell>
                        @php($pc = match ($row->order_priority) { 'critical','breakdown' => 'red', 'urgent' => 'amber', default => 'zinc' })
                        <flux:badge :color="$pc" size="sm">{{ $priorities[$row->order_priority] ?? $row->order_priority }}</flux:badge>
                    </flux:table.cell>
                    <flux:table.cell>
                        @php($sc = match ($row->status) {
                            'draft' => 'zinc', 'requested' => 'sky', 'approved' => 'blue', 'processing' => 'indigo',
                            'partially_issued' => 'amber', 'fully_issued' => 'lime', 'cancelled' => 'red', 'closed' => 'green', default => 'zinc',
                        })
                        <flux:badge :color="$sc" size="sm">{{ $statuses[$row->status] ?? $row->status }}</flux:badge>
                    </flux:table.cell>
                    <flux:table.cell>
                        <div class="flex items-center justify-end gap-1">
                            @can('internal_part_order.update')
                                <flux:button size="sm" variant="ghost" icon="pencil-square" :href="route('internal-part-order.edit', $row)" wire:navigate>Edit</flux:button>
                            @endcan
                            @can('internal_part_order.delete')
                                <flux:modal.trigger :name="'ipo-delete-' . $row->id">
                                    <flux:button size="sm" variant="ghost" icon="trash" />
                                </flux:modal.trigger>
                                <flux:modal :name="'ipo-delete-' . $row->id">
                                    <div class="space-y-4">
                                        <flux:heading size="lg">Delete {{ $row->order_no }}?</flux:heading>
                                        <flux:text>Cannot be undone. Cascades to lines and attachments.</flux:text>
                                        <div class="flex gap-2 justify-end">
                                            <flux:modal.close><flux:button variant="ghost">Cancel</flux:button></flux:modal.close>
                                            <flux:button variant="danger" wire:click="delete({{ $row->id }})" x-on:click="$flux.modal('ipo-delete-{{ $row->id }}').close()">Delete</flux:button>
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
                        <flux:icon.inbox-stack class="mx-auto mb-3 size-8 text-zinc-400" />
                        <div class="font-medium">No internal part orders yet</div>
                        <flux:text class="mt-1">Raise one from a job card or for general workshop use.</flux:text>
                    </flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>

    @if ($rows->hasPages())<div class="mt-4"><flux:pagination :paginator="$rows" /></div>@endif
</div>
