<div>
    <div class="mb-6 flex items-start justify-between gap-4">
        <div>
            <flux:heading size="xl" level="1">Invoice Correction</flux:heading>
            <flux:text class="mt-1">Advisor-initiated, admin-approved corrections to raised invoices.</flux:text>
        </div>
        <div class="flex items-center gap-2">
            @can('invoice_correction.view')
                <flux:button variant="ghost" icon="arrow-down-tray" wire:click="download">Correction Report</flux:button>
            @endcan
            @can('invoice_correction.create')
                <flux:button variant="primary" icon="plus" :href="route('invoice-correction.create')" wire:navigate>New Correction</flux:button>
            @endcan
        </div>
    </div>

    <div class="mb-6 grid grid-cols-2 md:grid-cols-4 gap-3">
        @foreach ([
            ['label' => 'Pending Requests', 'value' => $kpis['pending'], 'icon' => 'clock', 'filter' => 'requested'],
            ['label' => 'Approved Requests', 'value' => $kpis['approved'], 'icon' => 'check-circle', 'filter' => 'approved'],
            ['label' => 'Rejected Requests', 'value' => $kpis['rejected'], 'icon' => 'x-circle', 'filter' => 'rejected'],
            ['label' => 'Corrected Invoices', 'value' => $kpis['corrected'], 'icon' => 'document-check', 'filter' => 'corrected'],
        ] as $kpi)
            <button type="button" wire:click="$set('statusFilter', '{{ $kpi['filter'] }}')"
                class="text-left rounded-lg border border-zinc-200 dark:border-zinc-700 p-4 hover:border-zinc-400 dark:hover:border-zinc-500 transition">
                <div class="flex items-center gap-2 text-zinc-500"><flux:icon :name="$kpi['icon']" class="size-4" /><flux:text size="sm">{{ $kpi['label'] }}</flux:text></div>
                <div class="mt-1 text-2xl font-semibold tabular-nums">{{ $kpi['value'] }}</div>
            </button>
        @endforeach
    </div>

    <div class="mb-4 flex items-center gap-3 flex-wrap">
        <flux:input wire:model.live.debounce.300ms="search" placeholder="Search correction / invoice ref…" icon="magnifying-glass" clearable class="max-w-md" />
        <flux:select wire:model.live="statusFilter" variant="listbox" class="max-w-44">
            <flux:select.option value="all">All status</flux:select.option>
            @foreach ($statuses as $key => $label)<flux:select.option :value="$key">{{ $label }}</flux:select.option>@endforeach
        </flux:select>
        <flux:select wire:model.live="invoiceTypeFilter" variant="listbox" class="max-w-44">
            <flux:select.option value="all">All invoice types</flux:select.option>
            @foreach ($invoiceTypes as $key => $label)<flux:select.option :value="$key">{{ $label }}</flux:select.option>@endforeach
        </flux:select>
        @if ($search || $statusFilter !== 'all' || $invoiceTypeFilter !== 'all')
            <flux:button variant="ghost" size="sm" icon="x-mark" wire:click="clearFilters">Clear</flux:button>
        @endif
    </div>

    <flux:table>
        <flux:table.columns>
            <flux:table.column class="w-28" sortable :sorted="$sortBy === 'correction_no'" :direction="$sortDirection" wire:click="sort('correction_no')">Correction</flux:table.column>
            <flux:table.column>Invoice Ref / Customer</flux:table.column>
            <flux:table.column class="w-20 text-end">Lines</flux:table.column>
            <flux:table.column class="w-36">Status</flux:table.column>
            <flux:table.column class="w-28 text-end">Actions</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @forelse ($rows as $row)
                <flux:table.row :key="$row->id">
                    <flux:table.cell class="font-mono text-xs">{{ $row->correction_no }}</flux:table.cell>
                    <flux:table.cell class="text-sm">
                        <div class="font-mono">{{ $row->invoice_reference ?? '—' }}</div>
                        <div class="text-xs text-zinc-500 mt-0.5">{{ trim(($row->customer?->first_name ?? '').' '.($row->customer?->last_name ?? '')) }}</div>
                    </flux:table.cell>
                    <flux:table.cell class="text-end font-mono text-sm">{{ $row->items_count }}</flux:table.cell>
                    <flux:table.cell>
                        @php($sc = match ($row->status) {
                            'corrected' => 'lime',
                            'approved' => 'blue',
                            'under_review', 'requested' => 'amber',
                            'on_hold' => 'orange',
                            'rejected' => 'red',
                            default => 'zinc',
                        })
                        <flux:badge :color="$sc" size="sm">{{ $statuses[$row->status] ?? $row->status }}</flux:badge>
                    </flux:table.cell>
                    <flux:table.cell>
                        <div class="flex items-center justify-end gap-1">
                            @can('invoice_correction.update')
                                <flux:button size="sm" variant="ghost" icon="pencil-square" :href="route('invoice-correction.edit', $row)" wire:navigate>Edit</flux:button>
                            @endcan
                            @can('invoice_correction.delete')
                                <flux:modal.trigger :name="'ic-delete-' . $row->id"><flux:button size="sm" variant="ghost" icon="trash" /></flux:modal.trigger>
                                <flux:modal :name="'ic-delete-' . $row->id">
                                    <div class="space-y-4">
                                        <flux:heading size="lg">Delete {{ $row->correction_no }}?</flux:heading>
                                        <flux:text>This cannot be undone. Lines and attachments are removed too.</flux:text>
                                        <div class="flex gap-2 justify-end">
                                            <flux:modal.close><flux:button variant="ghost">Cancel</flux:button></flux:modal.close>
                                            <flux:button variant="danger" wire:click="delete({{ $row->id }})" x-on:click="$flux.modal('ic-delete-{{ $row->id }}').close()">Delete</flux:button>
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
                        <flux:icon.pencil-square class="mx-auto mb-3 size-8 text-zinc-400" />
                        <div class="font-medium">No invoice corrections yet</div>
                    </flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>

    @if ($rows->hasPages())<div class="mt-4"><flux:pagination :paginator="$rows" /></div>@endif
</div>
