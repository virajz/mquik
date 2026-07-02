<div>
    <div class="mb-6 flex items-start justify-between gap-4">
        <div>
            <flux:heading size="xl" level="1">Final Inspections</flux:heading>
            <flux:text class="mt-1">Pre-delivery quality check — checklist, results, photo evidence, rework &amp; completion status.</flux:text>
        </div>
        @can('final_inspection.create')
            <flux:button variant="primary" icon="plus" :href="route('final-inspection.create')" wire:navigate>New Inspection</flux:button>
        @endcan
    </div>

    <div class="mb-4 flex items-center gap-3 flex-wrap">
        <flux:input wire:model.live.debounce.300ms="search" placeholder="Search by FI no or JC no…" icon="magnifying-glass" clearable class="max-w-md" />
        <flux:select wire:model.live="statusFilter" variant="listbox" class="max-w-44">
            <flux:select.option value="all">All status</flux:select.option>
            @foreach ($statuses as $key => $label)
                <flux:select.option :value="$key">{{ $label }}</flux:select.option>
            @endforeach
        </flux:select>
    </div>

    <flux:table>
        <flux:table.columns>
            <flux:table.column class="w-28" sortable :sorted="$sortBy === 'inspection_no'" :direction="$sortDirection" wire:click="sort('inspection_no')">No.</flux:table.column>
            <flux:table.column>Job Card / Inspector</flux:table.column>
            <flux:table.column class="w-40">Template</flux:table.column>
            <flux:table.column class="w-20 text-center">Items</flux:table.column>
            <flux:table.column class="w-32" sortable :sorted="$sortBy === 'status'" :direction="$sortDirection" wire:click="sort('status')">Status</flux:table.column>
            <flux:table.column class="w-32" align="end">Actions</flux:table.column>
        </flux:table.columns>
        <flux:table.rows>
            @forelse ($rows as $row)
                <flux:table.row :key="$row->id">
                    <flux:table.cell class="font-mono text-xs">{{ $row->inspection_no ?? '—' }}</flux:table.cell>
                    <flux:table.cell>
                        <div class="font-mono font-medium text-sm">{{ $row->jobCard?->job_card_no ?? '—' }}</div>
                        <div class="text-xs text-zinc-500 mt-0.5">{{ $row->inspector?->name ?? '' }}</div>
                    </flux:table.cell>
                    <flux:table.cell class="text-sm">{{ $row->template?->name ?? '—' }}</flux:table.cell>
                    <flux:table.cell class="text-center font-mono text-sm">{{ $row->items_count }}</flux:table.cell>
                    <flux:table.cell>
                        @php($sc = match ($row->status) {
                            'pending' => 'zinc', 'in_progress' => 'blue', 'on_hold' => 'amber', 'completed' => 'lime',
                            'rework' => 'orange', 'cancelled' => 'red', 'not_applicable' => 'zinc', default => 'zinc',
                        })
                        <flux:badge :color="$sc" size="sm">{{ $statuses[$row->status] ?? $row->status }}</flux:badge>
                    </flux:table.cell>
                    <flux:table.cell>
                        <div class="flex items-center justify-end gap-1">
                            @can('final_inspection.update')
                                <flux:button size="sm" variant="ghost" icon="pencil-square" :href="route('final-inspection.edit', $row)" wire:navigate>Edit</flux:button>
                            @endcan
                            @can('final_inspection.delete')
                                <flux:modal.trigger :name="'fi-delete-' . $row->id">
                                    <flux:button size="sm" variant="ghost" icon="trash" />
                                </flux:modal.trigger>
                                <flux:modal :name="'fi-delete-' . $row->id">
                                    <div class="space-y-4">
                                        <flux:heading size="lg">Delete {{ $row->inspection_no }}?</flux:heading>
                                        <flux:text>Cannot be undone. Cascades to items and time logs.</flux:text>
                                        <div class="flex gap-2 justify-end">
                                            <flux:modal.close><flux:button variant="ghost">Cancel</flux:button></flux:modal.close>
                                            <flux:button variant="danger" wire:click="delete({{ $row->id }})" x-on:click="$flux.modal('fi-delete-{{ $row->id }}').close()">Delete</flux:button>
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
                        <flux:icon.check-badge class="mx-auto mb-3 size-8 text-zinc-400" />
                        <div class="font-medium">No final inspections yet</div>
                        <flux:text class="mt-1">Run a pre-delivery quality check on a completed job card.</flux:text>
                    </flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>

    @if ($rows->hasPages())<div class="mt-4"><flux:pagination :paginator="$rows" /></div>@endif
</div>
