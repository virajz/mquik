<div>
    <div class="mb-6 flex items-start justify-between gap-4">
        <div>
            <flux:heading size="xl" level="1">IPO Cancel Approval</flux:heading>
            <flux:text class="mt-1">Cancel un-issued parts on internal part orders — with approval.</flux:text>
        </div>
        <div class="flex items-center gap-2">
            @can('ipo_cancel_approval.view')
                <flux:button variant="ghost" icon="arrow-down-tray" wire:click="download">IPO Report</flux:button>
            @endcan
            @can('ipo_cancel_approval.create')
                <flux:button variant="primary" icon="plus" :href="route('ipo-cancel-approval.create')" wire:navigate>New Request</flux:button>
            @endcan
        </div>
    </div>

    <div class="mb-6 grid grid-cols-2 md:grid-cols-4 gap-3">
        @foreach ([
            ['label' => 'Under Review', 'value' => $kpis['under_review'], 'icon' => 'eye', 'filter' => 'under_review'],
            ['label' => 'Approved', 'value' => $kpis['approved'], 'icon' => 'check-circle', 'filter' => 'approved'],
            ['label' => 'Rejected', 'value' => $kpis['rejected'], 'icon' => 'x-circle', 'filter' => 'rejected'],
            ['label' => 'Closed', 'value' => $kpis['closed'], 'icon' => 'archive-box', 'filter' => 'cancelled'],
        ] as $kpi)
            <button type="button" wire:click="$set('statusFilter', '{{ $kpi['filter'] }}')"
                class="text-left rounded-lg border border-zinc-200 dark:border-zinc-700 p-4 hover:border-zinc-400 dark:hover:border-zinc-500 transition">
                <div class="flex items-center gap-2 text-zinc-500">
                    <flux:icon :name="$kpi['icon']" class="size-4" />
                    <flux:text size="sm">{{ $kpi['label'] }}</flux:text>
                </div>
                <div class="mt-1 text-2xl font-semibold tabular-nums">{{ $kpi['value'] }}</div>
            </button>
        @endforeach
    </div>

    <div class="mb-4 flex items-center gap-3 flex-wrap">
        <flux:input wire:model.live.debounce.300ms="search" placeholder="Search cancel no or job card…" icon="magnifying-glass" clearable class="max-w-md" />
        <flux:select wire:model.live="statusFilter" variant="listbox" class="max-w-56">
            <flux:select.option value="all">All status</flux:select.option>
            @foreach ($statuses as $key => $label)
                <flux:select.option :value="$key">{{ $label }}</flux:select.option>
            @endforeach
        </flux:select>
        @if ($search || $statusFilter !== 'all')
            <flux:button variant="ghost" size="sm" icon="x-mark" wire:click="clearFilters">Clear</flux:button>
        @endif
    </div>

    <flux:table>
        <flux:table.columns>
            <flux:table.column class="w-28" sortable :sorted="$sortBy === 'cancel_no'" :direction="$sortDirection" wire:click="sort('cancel_no')">Cancel No</flux:table.column>
            <flux:table.column class="w-28">IPO</flux:table.column>
            <flux:table.column>Part / Reason</flux:table.column>
            <flux:table.column>Requested By</flux:table.column>
            <flux:table.column class="w-44">Status</flux:table.column>
            <flux:table.column class="w-28 text-end">Actions</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @forelse ($rows as $row)
                <flux:table.row :key="$row->id">
                    <flux:table.cell class="font-mono text-xs">{{ $row->cancel_no }}</flux:table.cell>
                    <flux:table.cell class="font-mono text-xs">{{ $row->internalPartOrder?->order_no ?? '—' }}</flux:table.cell>
                    <flux:table.cell class="text-sm">
                        <div>{{ $row->spare?->name ?? '—' }}</div>
                        <div class="text-xs text-zinc-500 mt-0.5">{{ $reasons[$row->cancellation_reason] ?? '' }}</div>
                    </flux:table.cell>
                    <flux:table.cell class="text-sm text-zinc-500">{{ $row->employee?->name ?? '—' }}</flux:table.cell>
                    <flux:table.cell>
                        @php($sc = match ($row->status) {
                            'approved' => 'lime',
                            'rejected', 'cancelled' => 'red',
                            'reversed' => 'purple',
                            'needs_clarification' => 'amber',
                            default => 'blue',
                        })
                        <flux:badge :color="$sc" size="sm">{{ $statuses[$row->status] ?? $row->status }}</flux:badge>
                    </flux:table.cell>
                    <flux:table.cell>
                        <div class="flex items-center justify-end gap-1">
                            @can('ipo_cancel_approval.update')
                                <flux:button size="sm" variant="ghost" icon="pencil-square" :href="route('ipo-cancel-approval.edit', $row)" wire:navigate>Edit</flux:button>
                            @endcan
                            @can('ipo_cancel_approval.delete')
                                <flux:modal.trigger :name="'ica-delete-' . $row->id">
                                    <flux:button size="sm" variant="ghost" icon="trash" />
                                </flux:modal.trigger>
                                <flux:modal :name="'ica-delete-' . $row->id">
                                    <div class="space-y-4">
                                        <flux:heading size="lg">Delete {{ $row->cancel_no }}?</flux:heading>
                                        <flux:text>This cannot be undone. Attachments are removed too.</flux:text>
                                        <div class="flex gap-2 justify-end">
                                            <flux:modal.close><flux:button variant="ghost">Cancel</flux:button></flux:modal.close>
                                            <flux:button variant="danger" wire:click="delete({{ $row->id }})" x-on:click="$flux.modal('ica-delete-{{ $row->id }}').close()">Delete</flux:button>
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
                        <flux:icon.archive-box-x-mark class="mx-auto mb-3 size-8 text-zinc-400" />
                        <div class="font-medium">No IPO cancel requests yet</div>
                    </flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>

    @if ($rows->hasPages())<div class="mt-4"><flux:pagination :paginator="$rows" /></div>@endif
</div>
