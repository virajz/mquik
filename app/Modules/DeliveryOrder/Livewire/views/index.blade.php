<div>
    <div class="mb-6 flex items-start justify-between gap-4">
        <div>
            <flux:heading size="xl" level="1">Delivery Order (DO)</flux:heading>
            <flux:text class="mt-1">Insurance Delivery Order against a claim, with proforma-vs-DO amount check.</flux:text>
        </div>
        <div class="flex items-center gap-2">
            @can('delivery_order.view')
                <flux:button variant="ghost" icon="arrow-down-tray" wire:click="download">Delivery Order Report</flux:button>
            @endcan
            @can('delivery_order.create')
                <flux:button variant="primary" icon="plus" :href="route('delivery-order.create')" wire:navigate>New DO</flux:button>
            @endcan
        </div>
    </div>

    <div class="mb-6 grid grid-cols-1 md:grid-cols-3 gap-3">
        @foreach ([
            ['label' => 'DO Pending', 'value' => $kpis['pending'], 'icon' => 'clock', 'filter' => 'requested'],
            ['label' => 'DO Received', 'value' => $kpis['received'], 'icon' => 'check-circle', 'filter' => 'do_received'],
            ['label' => 'Amount Mismatch Cases', 'value' => $kpis['mismatch'], 'icon' => 'exclamation-triangle', 'filter' => null],
        ] as $kpi)
            <button type="button" @if ($kpi['filter']) wire:click="$set('statusFilter', '{{ $kpi['filter'] }}')" @else disabled @endif
                class="text-left rounded-lg border border-zinc-200 dark:border-zinc-700 p-4 @if ($kpi['filter']) hover:border-zinc-400 dark:hover:border-zinc-500 transition @endif">
                <div class="flex items-center gap-2 text-zinc-500"><flux:icon :name="$kpi['icon']" class="size-4" /><flux:text size="sm">{{ $kpi['label'] }}</flux:text></div>
                <div class="mt-1 text-2xl font-semibold tabular-nums @if (! $kpi['filter'] && $kpi['value'] > 0) text-red-600 dark:text-red-400 @endif">{{ $kpi['value'] }}</div>
            </button>
        @endforeach
    </div>

    <div class="mb-4 flex items-center gap-3 flex-wrap">
        <flux:input wire:model.live.debounce.300ms="search" placeholder="Search DO / claim / job card…" icon="magnifying-glass" clearable class="max-w-md" />
        <flux:select wire:model.live="statusFilter" variant="listbox" class="max-w-56">
            <flux:select.option value="all">All status</flux:select.option>
            @foreach ($statuses as $key => $label)<flux:select.option :value="$key">{{ $label }}</flux:select.option>@endforeach
        </flux:select>
        @if ($search || $statusFilter !== 'all')
            <flux:button variant="ghost" size="sm" icon="x-mark" wire:click="clearFilters">Clear</flux:button>
        @endif
    </div>

    <flux:table>
        <flux:table.columns>
            <flux:table.column class="w-28" sortable :sorted="$sortBy === 'do_no'" :direction="$sortDirection" wire:click="sort('do_no')">DO</flux:table.column>
            <flux:table.column>Job Card / Insurance</flux:table.column>
            <flux:table.column class="w-28 text-end">Proforma</flux:table.column>
            <flux:table.column class="w-28 text-end">DO Amt</flux:table.column>
            <flux:table.column class="w-44">Status</flux:table.column>
            <flux:table.column class="w-28 text-end">Actions</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @forelse ($rows as $row)
                <flux:table.row :key="$row->id">
                    <flux:table.cell class="font-mono text-xs">{{ $row->do_no }}</flux:table.cell>
                    <flux:table.cell class="text-sm">
                        <div class="font-mono">{{ $row->jobCard?->job_card_no ?? '—' }}</div>
                        <div class="text-xs text-zinc-500 mt-0.5">{{ $row->insuranceCompany?->name ?? '' }}</div>
                    </flux:table.cell>
                    <flux:table.cell class="text-end font-mono text-sm">{{ $row->proforma_amount !== null ? number_format((float) $row->proforma_amount, 2) : '—' }}</flux:table.cell>
                    <flux:table.cell class="text-end font-mono text-sm {{ $row->hasMismatch() ? 'text-red-600 dark:text-red-400 font-semibold' : '' }}">
                        {{ $row->do_amount !== null ? number_format((float) $row->do_amount, 2) : '—' }}
                        @if ($row->hasMismatch())<flux:icon.exclamation-triangle class="inline size-3 -mt-0.5 ml-0.5" />@endif
                    </flux:table.cell>
                    <flux:table.cell>
                        @php($sc = match ($row->status) {
                            'do_received', 'mismatch_approved' => 'lime',
                            'under_verification', 'requested', 'requested_to_settle' => 'amber',
                            'on_hold' => 'orange',
                            'cancelled' => 'red',
                            default => 'zinc',
                        })
                        <flux:badge :color="$sc" size="sm">{{ $statuses[$row->status] ?? $row->status }}</flux:badge>
                    </flux:table.cell>
                    <flux:table.cell>
                        <div class="flex items-center justify-end gap-1">
                            @can('delivery_order.update')
                                <flux:button size="sm" variant="ghost" icon="pencil-square" :href="route('delivery-order.edit', $row)" wire:navigate>Edit</flux:button>
                            @endcan
                            @can('delivery_order.delete')
                                <flux:modal.trigger :name="'do-delete-' . $row->id"><flux:button size="sm" variant="ghost" icon="trash" /></flux:modal.trigger>
                                <flux:modal :name="'do-delete-' . $row->id">
                                    <div class="space-y-4">
                                        <flux:heading size="lg">Delete {{ $row->do_no }}?</flux:heading>
                                        <flux:text>This cannot be undone. Attachments are removed too.</flux:text>
                                        <div class="flex gap-2 justify-end">
                                            <flux:modal.close><flux:button variant="ghost">Cancel</flux:button></flux:modal.close>
                                            <flux:button variant="danger" wire:click="delete({{ $row->id }})" x-on:click="$flux.modal('do-delete-{{ $row->id }}').close()">Delete</flux:button>
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
                        <flux:icon.document-check class="mx-auto mb-3 size-8 text-zinc-400" />
                        <div class="font-medium">No delivery orders yet</div>
                    </flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>

    @if ($rows->hasPages())<div class="mt-4"><flux:pagination :paginator="$rows" /></div>@endif
</div>
